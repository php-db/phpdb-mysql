<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Pdo;

use Override;
use PDO;
use PDOException;
use PDOStatement;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Exception;

use function array_diff_key;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function strtolower;

// @mago-expect lint:cyclomatic-complexity
class Connection extends AbstractPdoConnection
{
    /**
     * Constructor
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        PDO|array $connectionParameters,
    ) {
        if (is_array($connectionParameters)) {
            $this->setConnectionParameters($connectionParameters);

            return;
        }

        $this->setResource($connectionParameters);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\InvalidConnectionParametersException
     * @throws Exception\RuntimeException
     */
    // @mago-expect lint:halstead
    #[Override]
    public function connect(): ConnectionInterface
    {
        if ($this->resource) {
            return $this;
        }

        $dsn        = null;
        $username   = null;
        $password   = null;
        $hostname   = null;
        $port       = null;
        $charset    = null;
        $database   = null;
        $unixSocket = null;
        $version    = null;
        $options    = [];

        foreach ($this->connectionParameters as $key => $value) {
            $result = match (strtolower($key)) {
                'dsn'                                => $dsn = (string) $value,
                'user', 'username'                   => $username = (string) $value,
                'password', 'passwd', 'pw'           => $password = (string) $value,
                'host', 'hostname'                   => $hostname = (string) $value,
                'port'                               => $port = (int) $value,
                'charset'                            => $charset = (string) $value,
                'dbname', 'database', 'db', 'schema' => $database = (string) $value,
                'unix_socket'                        => $unixSocket = (string) $value,
                'version'                            => $version = (string) $value,
                // todo(@tyrsson): should we suppport sslmode for pdo pgsql?
                'driver_options' => (static function (&$options, $value): void {
                    $value   = (array) $value;
                    $options = array_diff_key($options, $value) + $value;
                })($options, $value),
                default          => $options[$key] = $value,
            };
        }
        unset($result);

        if (null !== $hostname && null !== $unixSocket) {
            throw new Exception\InvalidConnectionParametersException(
                'Ambiguous connection parameters, both hostname and unix_socket parameters were set',
                $this->connectionParameters,
            );
        }

        if (null === $dsn) {
            $dsn = [];
            if (null !== $database) {
                $dsn[] = "dbname={$database}";
            }
            if (null !== $hostname) {
                $dsn[] = "host={$hostname}";
            }
            if (null !== $port) {
                $dsn[] = "port={$port}";
            }
            if (null !== $charset) {
                $dsn[] = "charset={$charset}";
            }
            if (null !== $unixSocket) {
                $dsn[] = "unix_socket={$unixSocket}";
            }
            if (null !== $version) {
                $dsn[] = "version={$version}";
            }
            $dsn = 'mysql:' . implode(';', $dsn);
        }

        if (! is_string($dsn)) {
            throw new Exception\InvalidConnectionParametersException(
                'A dsn was not provided or could not be constructed from your parameters',
                $this->connectionParameters,
            );
        }

        $this->dsn = $dsn;

        try {
            $this->resource = new PDO($dsn, $username, $password, $options);
            $this->resource->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->driverName = strtolower($this->resource->getAttribute(PDO::ATTR_DRIVER_NAME));
        } catch (PDOException $e) {
            $code = $e->getCode();
            if (! is_int($code)) {
                $code = 0;
            }
            throw new Exception\RuntimeException("Connect Error: {$e->getMessage()}", $code, $e);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getCurrentSchema(): string|false
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        /** @var PDOStatement $result */
        $result = $this->resource->query('SELECT DATABASE()');
        if ($result instanceof PDOStatement) {
            return $result->fetchColumn();
        }

        return false;
    }

    #[Override]
    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        try {
            return $this->resource->lastInsertId($name);
        } catch (\Exception) {
            // do nothing
        }

        return false;
    }
}
