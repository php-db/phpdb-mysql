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

class Connection extends AbstractConnection implements DriverAwareInterface
{
    protected Driver $driver;

    protected ?MysqliConnectionParameters $params = null;

    /** @var mysqli */
    protected $resource;

    /**
     * Constructor
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        array|mysqli $connectionInfo,
    ) {
        if (is_array($connectionInfo)) {
            $this->setConnectionParameters($connectionInfo);
        }

        if ($connectionInfo instanceof mysqli) {
            $this->setResource($connectionInfo);
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
    #[Override]
    public function connect(): ConnectionInterface
    {
        if ($this->resource instanceof mysqli) {
            return $this;
        }

        $params = $this->params ??= MysqliConnectionParameters::fromArray($this->connectionParameters);

        $this->resource = $this->createResource();

        if ([] !== $params->driverOptions) {
            $this->applyDriverOptions($this->resource, $params->driverOptions);
        }

        $flags = $this->applySsl($this->resource, $params);

        $this->performRealConnect($this->resource, $params, $flags);

        if (! empty($params->charset)) {
            $this->resource->set_charset($params->charset);
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

        return $this->driver->createResult($resultResource === true ? $this->resource : $resultResource);
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

    /**
     * Set connection parameters
     *
     * Also normalizes the raw array into a {@see MysqliConnectionParameters} value
     * object, cached for the lifetime of the connection so that {@see connect()}
     * does not need to re-parse the raw array on every call.
     *
     * @return $this Provides a fluent interface
     */
    #[Override]
    public function setConnectionParameters(array $connectionParameters): ConnectionInterface
    {
        parent::setConnectionParameters($connectionParameters);
        $this->params = MysqliConnectionParameters::fromArray($connectionParameters);

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
     * todo: why do we have this random method here?
     *
     * @return mysqli
     */
    protected function createResource()
    {
        return new mysqli();
    }

    /**
     * Apply mysqli `driver_options` to the resource prior to connecting.
     *
     * String keys are resolved against defined `MYSQLI_*` constants (case-insensitively);
     * unresolvable string keys are ignored. Integer keys (e.g. raw `MYSQLI_*` constants)
     * are passed through unchanged.
     *
     * @param array<int|string, mixed> $driverOptions
     */
    private function applyDriverOptions(mysqli $resource, array $driverOptions): void
    {
        foreach ($driverOptions as $option => $value) {
            if (is_string($option)) {
                $option = strtoupper($option);
                if (! defined($option)) {
                    continue;
                }
                $option = constant($option);
            }
            $resource->options($option, $value);
        }
    }

    /**
     * Configure SSL on the resource, if requested, and return the connect flags to use.
     *
     * Even though mysqli docs are not quite clear on this, MYSQLI_CLIENT_SSL needs to be
     * set to make sure SSL is used. ssl_set can also cause it to be implicitly set, but
     * only when any of the parameters is non-empty.
     */
    private function applySsl(mysqli $resource, MysqliConnectionParameters $params): ?int
    {
        if (! $params->ssl->enabled || $params->socket) {
            return null;
        }

        $flags = MYSQLI_CLIENT_SSL;
        $resource->ssl_set(
            $params->ssl->key,
            $params->ssl->cert,
            $params->ssl->caCert,
            $params->ssl->caPath,
            $params->ssl->cipher,
        );

        // MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT is not valid option, needs to be set as flag
        if (isset($params->driverOptions[MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT])) {
            $flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
        }

        return $flags;
    }

    /**
     * @throws Exception\RuntimeException
     */
    private function performRealConnect(mysqli $resource, MysqliConnectionParameters $params, ?int $flags): void
    {
        try {
            null === $flags
                ? $resource->real_connect(
                    $params->hostname,
                    $params->username,
                    $params->password,
                    $params->database,
                    $params->port,
                    $params->socket,
                )
                : $resource->real_connect(
                    $params->hostname,
                    $params->username,
                    $params->password,
                    $params->database,
                    $params->port,
                    $params->socket,
                    $flags,
                );
        } catch (GenericException) {
            throw new Exception\RuntimeException(
                'Connection error',
                $resource->connect_errno,
                new Exception\ErrorException($resource->connect_error, $resource->connect_errno),
            );
        }

        if ($resource->connect_error) {
            throw new Exception\RuntimeException(
                'Connection error',
                $resource->connect_errno,
                new Exception\ErrorException($resource->connect_error, $resource->connect_errno),
            );
        }
    }
}
