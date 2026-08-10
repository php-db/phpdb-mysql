<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use Exception as GenericException;
use mysqli;
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
class Connection extends AbstractConnection implements DriverAwareInterface
{
    protected Driver $driver;

    /** @var mysqli */
    protected $resource;

    /**
     * Constructor
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

        if (null !== $connectionInfo) {
            throw new Exception\InvalidArgumentException(
                '$connection must be an array of parameters, a mysqli object or null',
            );
        }
    }

    /** @inheritDoc */
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

    /** @inheritDoc */
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

    /** @inheritDoc */
    // @mago-expect lint:halstead
    #[Override]
    public function connect(): ConnectionInterface
    {
        if ($this->resource instanceof mysqli) {
            return $this;
        }

        /** @var array $p */
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
        /** @var int|null $port */
        $port = null === ($p['port'] ?? null) ? null : (int) $p['port'];
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

        if ($this->resource->connect_error) {
            throw new Exception\RuntimeException(
                'Connection error',
                $this->resource->connect_errno,
                new Exception\ErrorException($this->resource->connect_error, $this->resource->connect_errno),
            );
        }

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
     * @throws Exception\InvalidQueryException
     */
    #[Override]
    public function execute($sql): ?ResultInterface
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $this->profiler?->profilerStart($sql);

        $resultResource = $this->resource->query($sql);

        $this->profiler?->profilerFinish($sql);

        // if the returnValue is something other than a mysqli_result, bypass wrapping it
        if (false === $resultResource) {
            throw new Exception\InvalidQueryException($this->resource->error);
        }

        return $this->driver->createResult(true === $resultResource ? $this->resource : $resultResource);
    }

    /** @inheritDoc */
    #[Override]
    public function getCurrentSchema(): string|false
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        $result = $this->resource->query('SELECT DATABASE()');
        $r      = $result->fetch_row();

        return $r[0];
    }

    /** @inheritDoc */
    #[Override]
    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        return $this->resource->insert_id;
    }

    /** @inheritDoc */
    public function isConnected(): bool
    {
        return $this->resource instanceof mysqli;
    }

    /** @inheritDoc */
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
    protected function createResource()
    {
        return new mysqli();
    }
}
