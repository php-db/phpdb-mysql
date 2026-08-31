<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use Exception as GenericException;
use mysqli;
use mysqli_result;
use Override;
use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverAwareInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Exception\InvalidArgumentException;

use function constant;
use function defined;
use function is_array;
use function is_string;
use function strtoupper;

use const MYSQLI_CLIENT_SSL;
use const MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;

// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
// @mago-expect lint:too-many-methods
// @mago-expect analysis:class-must-be-final
class Connection extends AbstractConnection implements DriverAwareInterface
{
    protected ?DriverInterface $driver = null;

    protected ?string $driverName = null;

    /** @var mysqli */
    protected $resource;

    /**
     * Constructor
     *
     * @param array<string, mixed>|mysqli|null $connectionInfo
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        array|mysqli|null $connectionInfo = null,
    ) {
        if (is_array($connectionInfo)) {
            $this->setConnectionParameters($connectionInfo);

            return;
        }

        if ($connectionInfo instanceof mysqli) {
            $this->setResource($connectionInfo);

            return;
        }
    }

    /**
     * @inheritDoc
     *
     * @throws Exception\ExceptionInterface
     */
    #[Override]
    public function beginTransaction(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $this->resource->autocommit(false);
        $this->inTransaction = true;

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception\ExceptionInterface
     */
    #[Override]
    public function commit(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $this->resource->commit();
        $this->inTransaction = false;
        $this->resource->autocommit(true);

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception\ExceptionInterface
     */
    // @mago-expect lint:halstead
    #[Override]
    public function connect(): ConnectionInterface
    {
        if ($this->resource instanceof mysqli) {
            return $this;
        }

        $p = $this->connectionParameters;

        // given a list of key names, test for existence in $p
        /** @var string[] $names */
        $findParameterValue = static function (array $names) use ($p): ?string {
            foreach ($names as $name) {
                if (null !== ($p[$name] ?? null)) {
                    return $p[$name];
                }
            }

            return null;
        };

        /** @var string|null $hostname */
        $hostname = $findParameterValue(['hostname', 'host']);
        $username = $findParameterValue(['username', 'user']);
        $password = $findParameterValue(['password', 'passwd', 'pw']);
        $database = $findParameterValue(['database', 'dbname', 'db', 'schema']);
        $port     = null === ($p['port'] ?? null) ? null : (int) $p['port'];
        /** @var string|null $socket */
        $socket = $p['socket'] ?? null;

        // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        $useSSL     = $p['use_ssl'] ?? 0;
        $clientKey  = $p['client_key'] ?? '';
        $clientCert = $p['client_cert'] ?? '';
        $caCert     = $p['ca_cert'] ?? '';
        $caPath     = $p['ca_path'] ?? '';
        $cipher     = $p['cipher'] ?? '';

        $this->resource = $this->createResource();

        if ([] !== ($p['driver_options'] ?? [])) {
            foreach ($p['driver_options'] as $option => $value) {
                if (is_string($option)) {
                    $option = strtoupper($option);
                    if (! defined($option)) {
                        continue;
                    }
                    $option = constant($option);
                }
                $this->resource->options($option, $value);
            }
        }

        $flags = null;

        // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        if ($useSSL && ! $socket) {
            // Even though mysqli docs are not quite clear on this, MYSQLI_CLIENT_SSL
            // needs to be set to make sure SSL is used. ssl_set can also cause it to
            // be implicitly set, but only when any of the parameters is non-empty.
            $flags = MYSQLI_CLIENT_SSL;
            $this->resource->ssl_set($clientKey, $clientCert, $caCert, $caPath, $cipher);
            //MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT is not valid option, needs to be set as flag
            if (
                null !== ($p['driver_options'][MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT] ?? null)
            ) {
                $flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
        }

        try {
            null === $flags
                ? $this->resource->real_connect($hostname, $username, $password, $database, $port, $socket)
                : $this->resource->real_connect($hostname, $username, $password, $database, $port, $socket, $flags);
        } catch (GenericException) {
            throw new Exception\RuntimeException(
                'Connection error',
                $this->resource->connect_errno,
                new Exception\ErrorException($this->resource->connect_error, $this->resource->connect_errno),
            );
        }

        // real_connect() returning true never leaves connect_error populated; kept as a guard
        // for exotic driver builds.
        // @codeCoverageIgnoreStart
        if ($this->resource->connect_error) {
            throw new Exception\RuntimeException(
                'Connection error',
                $this->resource->connect_errno,
                new Exception\ErrorException($this->resource->connect_error, $this->resource->connect_errno),
            );
        }
        // @codeCoverageIgnoreEnd

        if ('' !== ($p['charset'] ?? '')) {
            $this->resource->set_charset($p['charset']);
        }

        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function disconnect(): ConnectionInterface
    {
        if ($this->resource instanceof mysqli) {
            $this->resource->close();
        }
        $this->resource = null;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\ExceptionInterface
     */
    #[Override]
    public function execute(string $sql): ?ResultInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $this->profiler?->profilerStart($sql);

        $resultResource = $this->resource->query($sql);

        $this->profiler?->profilerFinish();

        // if the returnValue is something other than a mysqli_result, bypass wrapping it
        if (false === $resultResource) {
            throw new Exception\InvalidQueryException($this->resource->error);
        }

        if (null === $this->driver) {
            throw new Exception\RuntimeException('Cannot execute without a driver; call setDriver() first.');
        }

        // @mago-expect analysis:invalid-argument - DriverInterface::createResult() is documented with a
        // generic `resource` type to stay valid across every RDBMS platform (see php-db/phpdb#170 for a
        // proposed @template-based fix); this class always passes real mysqli|mysqli_result objects, which
        // is correct for this concrete implementation.
        return $this->driver->createResult(true === $resultResource ? $this->resource : $resultResource);
    }

    /**
     * @inheritDoc
     *
     * @throws Exception\ExceptionInterface
     */
    #[Override]
    public function getCurrentSchema(): string|false
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $result = $this->resource->query('SELECT DATABASE()');
        if (! $result instanceof mysqli_result) {
            throw new Exception\RuntimeException('Failed to query current schema');
        }

        $r = $result->fetch_row();
        // fetch_row() only returns false on a server failure between query and fetch.
        // @codeCoverageIgnoreStart
        if (false === $r) {
            throw new Exception\RuntimeException($this->resource->error);
        }
        // @codeCoverageIgnoreEnd

        /** @var array{0: string|null}|null $r */
        if (null === $r || null === $r[0]) {
            return false;
        }

        return $r[0];
    }

    /** @inheritDoc */
    #[Override]
    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        return $this->resource->insert_id;
    }

    /** @inheritDoc */
    #[Override]
    public function isConnected(): bool
    {
        return $this->resource instanceof mysqli;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception\ExceptionInterface
     */
    #[Override]
    public function rollback(): ConnectionInterface
    {
        if (! $this->isConnected()) {
            throw new Exception\RuntimeException('Must be connected before you can rollback.');
        }

        if (! $this->inTransaction) {
            throw new Exception\RuntimeException('Must call beginTransaction() before you can rollback.');
        }

        $this->resource->rollback();
        $this->resource->autocommit(true);
        $this->inTransaction = false;

        return $this;
    }

    #[Override]
    public function setDriver(DriverInterface $driver): DriverAwareInterface
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Set resource
     *
     * @return $this Provides a fluent interface
     */
    public function setResource(mysqli $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Create a new mysqli resource
     *
     * todo(@tyrsson): why do we have this random method here?
     *
     * @return mysqli
     */
    protected function createResource(): mysqli
    {
        return new mysqli();
    }
}
