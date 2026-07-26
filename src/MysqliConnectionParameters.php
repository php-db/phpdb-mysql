<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use function is_array;
use function strtolower;

/**
 * Immutable, normalized representation of the array accepted by
 * {@see \PhpDb\Mysql\Connection::setConnectionParameters()}.
 *
 * Resolves the various accepted key aliases (e.g. `hostname`/`host`) once, so
 * {@see \PhpDb\Mysql\Connection::connect()} can consume typed properties instead of
 * re-parsing a raw array on every call.
 */
final readonly class MysqliConnectionParameters
{
    /**
     * @param array<int|string, mixed> $driverOptions
     */
    public function __construct(
        public ?string $hostname = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $database = null,
        public ?int $port = null,
        public ?string $socket = null,
        public ?string $charset = null,
        public array $driverOptions = [],
        public MysqliSslOptions $ssl = new MysqliSslOptions(),
    ) {}

    /**
     * @param array<string, mixed> $params
     */
    public static function fromArray(array $params): self
    {
        $hostname      = $username = $password = $database = $port = $socket = $charset = null;
        $driverOptions = [];

        foreach ($params as $key => $value) {
            $result = match (strtolower((string) $key)) {
                'hostname', 'host'                   => $hostname = (string) $value,
                'username', 'user'                   => $username = (string) $value,
                'password', 'passwd', 'pw'           => $password = (string) $value,
                'database', 'dbname', 'db', 'schema' => $database = (string) $value,
                'port'                               => $port = (int) $value,
                'socket'                             => $socket = (string) $value,
                'charset'                            => $charset = (string) $value,
                'driver_options'                     => $driverOptions = is_array($value) ? $value : [],
                default                              => null,
            };
        }
        unset($result);

        return new self(
            hostname     : $hostname,
            username     : $username,
            password     : $password,
            database     : $database,
            port         : $port,
            socket       : $socket,
            charset      : $charset,
            driverOptions: $driverOptions,
            ssl          : MysqliSslOptions::fromArray($params),
        );
    }
}
