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

// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
final class Connection extends AbstractPdoConnection
{
    // @mago-expect analysis:write-only-property - read by the parent's final AbstractPdoConnection::getDsn()
    protected ?string $dsn = null;

    // @mago-expect analysis:write-only-property - read by AbstractConnection::getDriverName()
    protected ?string $driverName = null;

    /**
     * Constructor
     *
     * @param array<string, mixed>|PDO $connectionParameters
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
                $dsn[] = "dbname={$this->getDsnParameter('dbname', $database)}";
            }
            if (null !== $hostname) {
                $dsn[] = "host={$this->getDsnParameter('host', $hostname)}";
            }
            if (null !== $port) {
                $dsn[] = "port={$port}";
            }
            if (null !== $charset) {
                $dsn[] = "charset={$this->getDsnParameter('charset', $charset)}";
            }
            if (null !== $unixSocket) {
                $dsn[] = "unix_socket={$this->getDsnParameter('unix_socket', $unixSocket)}";
            }
            if (null !== $version) {
                $dsn[] = "version={$this->getDsnParameter('version', $version)}";
            }
            $dsn = 'mysql:' . implode(';', $dsn);
        }

        $this->dsn = $dsn;

        try {
            $this->resource = new PDO($dsn, $username, $password, $options);
            $this->resource->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->driverName = strtolower((string) $this->resource->getAttribute(PDO::ATTR_DRIVER_NAME));
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
     *
     * @throws Exception\ExceptionInterface
     * @throws PDOException
     */
    #[Override]
    public function getCurrentSchema(): string|false
    {
        if (! $this->isConnected()) {
            $this->connect();
        }

        if (null === $this->resource) {
            throw new Exception\RuntimeException(
                'Cannot query current schema without a connected resource; call connect() first.',
            );
        }

        $result = $this->resource->query('SELECT DATABASE()');
        if (! $result instanceof PDOStatement) {
            return false;
        }

        /** @var string|false|null $value */
        $value = $result->fetchColumn();
        return is_string($value) ? $value : false;
    }

    #[Override]
    public function getLastGeneratedValue(?string $name = null): string|int|false|null
    {
        if (null === $this->resource) {
            return false;
        }

        try {
            return $this->resource->lastInsertId($name);
        } catch (PDOException) {
            // not all pdo drivers support lastInsertId; fall through to false
            // @mago-expect lint:no-empty-catch-clause
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
                $this->connectionParameters,
            );
        }

        return $value;
    }
}
