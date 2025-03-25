<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Mysql\Adapter;
use Laminas\Db\Mysql\Driver\Pdo\Driver;
use Laminas\Db\Mysql\Platform;

use function getenv;

/** @psalm-suppress MissingConstructor */
trait AdapterTrait
{
    protected AdapterInterface&Adapter $adapter;

    protected function setUp(): void
    {
        $dbConfig = [
            'driver'   => Driver::class,
            'database' => getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE'),
            'hostname' => getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_HOSTNAME'),
            'username' => getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_USERNAME'),
            'password' => getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_PASSWORD'),
            'port'     => getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_PORT'),
        ];

        /** @var DriverInterface */
        $driver        = $this->getDriverFactory()($dbConfig);
        $this->adapter = new Adapter(
            $dbConfig,
            $driver,
            new Platform($driver)
        );
    }

    protected function getHostname(): array|string|false
    {
        return getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_HOSTNAME');
    }

    private function getDriverFactory(): callable
    {
        return static function (array $dbConfig): Driver {
            return new Driver($dbConfig);
        };
    }
}
