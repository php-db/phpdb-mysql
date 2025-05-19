<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo\Mysql;

use Laminas\Db\Adapter\Mysql\Adapter;
use Override;

use function getenv;

trait AdapterTrait
{
    protected ?string $hostname = 'localhost';

    #[Override]
    protected function setUp(): void
    {
        if (! (bool) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL')) {
            $this->markTestSkipped('pdo_mysql integration tests are not enabled!');
        }

        $this->adapter = new Adapter([
            'driver'   => 'pdo_mysql',
            'database' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_DATABASE'),
            'hostname' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_HOSTNAME'),
            'username' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_USERNAME'),
            'password' => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_PASSWORD'),
        ]);

        $this->hostname = (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_HOSTNAME');
    }
}
