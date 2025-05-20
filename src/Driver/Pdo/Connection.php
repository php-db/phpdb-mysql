<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Driver\Pdo\AbstractPdoConnection;
use Laminas\Db\Adapter\Exception;
use Override;
use PDOException;
use PDOStatement;

use function array_diff_key;
use function implode;
use function is_int;
use function str_replace;
use function strtolower;
use function substr;

class Connection extends AbstractPdoConnection
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function getCurrentSchema(): string|bool
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

    /**
     * @inheritDoc
     *
     * @throws Exception\InvalidConnectionParametersException
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function connect(): static
    {
        if ($this->resource) {
            return $this;
        }

        $dsn     = $username = $password = $hostname = $database = null;
        $options = [];
        foreach ($this->connectionParameters as $key => $value) {
            switch (strtolower($key)) {
                case 'dsn':
                    $dsn = $value;
                    break;
                case 'driver':
                    $value = strtolower((string) $value);
                    if (str_starts_with($value, 'pdo')) {
                        $pdoDriver = str_replace(['-', '_', ' '], '', $value);
                        $pdoDriver = substr($pdoDriver, 3) ?: '';
                    }
                    break;
                case 'pdodriver':
                    $pdoDriver = (string) $value;
                    break;
                case 'user':
                case 'username':
                    $username = (string) $value;
                    break;
                case 'pass':
                case 'password':
                    $password = (string) $value;
                    break;
                case 'host':
                case 'hostname':
                    $hostname = (string) $value;
                    break;
                case 'port':
                    $port = (int) $value;
                    break;
                case 'database':
                case 'dbname':
                    $database = (string) $value;
                    break;
                case 'charset':
                    $charset = (string) $value;
                    break;
                case 'unix_socket':
                    $unixSocket = (string) $value;
                    break;
                case 'version':
                    $version = (string) $value;
                    break;
                case 'driver_options':
                case 'options':
                    $value   = (array) $value;
                    $options = array_diff_key($options, $value) + $value;
                    break;
                default:
                    $options[$key] = $value;
                    break;
            }
        }

        if (isset($hostname) && isset($unixSocket)) {
            throw new Exception\InvalidConnectionParametersException(
                'Ambiguous connection parameters, both hostname and unix_socket parameters were set',
                $this->connectionParameters
            );
        }

        if (! isset($dsn) && isset($pdoDriver)) {
            $dsn = [];
            if (isset($database)) {
                $dsn[] = "dbname={$database}";
            }
            if (isset($hostname)) {
                $dsn[] = "host={$hostname}";
            }
            if (isset($port)) {
                $dsn[] = "port={$port}";
            }
            if (isset($charset) && $pdoDriver !== 'pgsql') {
                $dsn[] = "charset={$charset}";
            }
            if (isset($unixSocket)) {
                $dsn[] = "unix_socket={$unixSocket}";
            }
            if (isset($version)) {
                $dsn[] = "version={$version}";
            }
            $dsn = $pdoDriver . ':' . implode(';', $dsn);
        } elseif (! isset($dsn)) {
            throw new Exception\InvalidConnectionParametersException(
                'A dsn was not provided or could not be constructed from your parameters',
                $this->connectionParameters
            );
        }

        $this->dsn = $dsn;

        try {
            $this->resource = new \PDO($dsn, $username, $password, $options);
            $this->resource->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->driverName = strtolower($this->resource->getAttribute(\PDO::ATTR_DRIVER_NAME));
        } catch (PDOException $e) {
            $code = $e->getCode();
            if (! is_int($code)) {
                $code = 0;
            }
            throw new Exception\RuntimeException('Connect Error: ' . $e->getMessage(), $code, $e);
        }

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @param string $name
     */
    #[Override]
    public function getLastGeneratedValue($name = null): string|int|bool|null
    {
        try {
            return $this->resource->lastInsertId($name);
        } catch (\Exception) {
            // do nothing
        }

        return false;
    }
}
