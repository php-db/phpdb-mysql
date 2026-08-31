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
use function preg_match;
use function sprintf;
use function strtolower;

class Connection extends AbstractPdoConnection
{
    /**
     * Constructor
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        PDO|array $connectionParameters
    ) {
        if (is_array($connectionParameters)) {
            $this->setConnectionParameters($connectionParameters);
        } elseif ($connectionParameters instanceof PDO) {
            $this->setResource($connectionParameters);
        }
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

    /**
     * Return a value that is safe to interpolate into a generated DSN.
     *
     * @todo Promote to AbstractPdoConnection in php-db/phpdb as a protected method once a second
     *       PDO driver package needs it — the validation is generic to all semicolon-delimited
     *       PDO DSN formats and has no MySQL-specific dependencies.
     *
     * @throws Exception\InvalidConnectionParametersException If the value contains DSN control characters.
     */
    private function getDsnParameter(string $name, string $value): string
    {
        if (preg_match('/[;\x00-\x1f]/', $value) === 1) {
            throw new Exception\InvalidConnectionParametersException(
                sprintf('The "%s" connection parameter contains invalid characters', $name),
                $this->connectionParameters
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\InvalidConnectionParametersException
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function connect(): ConnectionInterface
    {
        if ($this->resource) {
            return $this;
        }

        $dsn     = $username = $password = $hostname = $port = $charset = $database = $unixSocket = $version = null;
        $options = [];

        foreach ($this->connectionParameters as $key => $value) {
            $result = match (strtolower($key)) {
                'dsn'                                => $dsn        = (string) $value,
                'user', 'username'                   => $username   = (string) $value,
                'password', 'passwd', 'pw'           => $password   = (string) $value,
                'host', 'hostname'                   => $hostname   = (string) $value,
                'port'                               => $port       = (int) $value,
                'charset'                            => $charset    = (string) $value,
                'dbname', 'database', 'db', 'schema' => $database   = (string) $value,
                'unix_socket'                        => $unixSocket = (string) $value,
                'version'                            => $version    = (string) $value,
                // todo: should we suppport sslmode for pdo pgsql?
                'driver_options' => (function (&$options, $value): void {
                    $value   = (array) $value;
                    $options = array_diff_key($options, $value) + $value;
                })($options, $value),
                default => $options[$key] = $value,
            };
        }
        unset($result);

        if (isset($hostname) && isset($unixSocket)) {
            throw new Exception\InvalidConnectionParametersException(
                'Ambiguous connection parameters, both hostname and unix_socket parameters were set',
                $this->connectionParameters
            );
        }

        if (! isset($dsn)) {
            $dsn = [];
            if (isset($database)) {
                $dsn[] = 'dbname=' . $this->getDsnParameter('dbname', $database);
            }
            if (isset($hostname)) {
                $dsn[] = 'host=' . $this->getDsnParameter('host', $hostname);
            }
            if (isset($port)) {
                $dsn[] = 'port=' . $port;
            }
            if (isset($charset)) {
                $dsn[] = 'charset=' . $this->getDsnParameter('charset', $charset);
            }
            if (isset($unixSocket)) {
                $dsn[] = 'unix_socket=' . $this->getDsnParameter('unix_socket', $unixSocket);
            }
            if (isset($version)) {
                $dsn[] = 'version=' . $this->getDsnParameter('version', $version);
            }
            $dsn = 'mysql:' . implode(';', $dsn);
        }

        if (! is_string($dsn)) {
            throw new Exception\InvalidConnectionParametersException(
                'A dsn was not provided or could not be constructed from your parameters',
                $this->connectionParameters
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
            throw new Exception\RuntimeException('Connect Error: ' . $e->getMessage(), $code, $e);
        }

        return $this;
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
