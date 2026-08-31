<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\FixtureLoader;

use Exception;
use PDO;

use function file_get_contents;
use function getenv;
use function print_r;
use function sprintf;

final class MysqlFixtureLoader implements FixtureLoaderInterface
{
    private string $fixtureFile = __DIR__ . '/../TestFixtures/mysql.sql';

    private ?PDO $pdo = null;

    /**
     * @throws Exception
     */
    public function createDatabase(): void
    {
        $this->connect();

        if (
            false === $this->pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS %s',
                getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
            ))
        ) {
            throw new Exception(sprintf(
                'I cannot create the MySQL %s test database: %s',
                getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
                print_r($this->pdo->errorInfo(), return: true),
            ));
        }

        $this->pdo->exec('USE ' . getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'));

        if (false === $this->pdo->exec(file_get_contents($this->fixtureFile))) {
            throw new Exception(sprintf(
                'I cannot create the table for %s database. Check the %s file. %s ',
                getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
                $this->fixtureFile,
                print_r($this->pdo->errorInfo(), return: true),
            ));
        }

        $this->disconnect();
    }

    public function dropDatabase(): void
    {
        $this->connect();

        $this->pdo->exec(sprintf(
            'DROP DATABASE IF EXISTS %s',
            getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
        ));

        $this->disconnect();
    }

    protected function connect(): void
    {
        $dsn = 'mysql:host=' . getenv('TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME');
        if (getenv('TESTS_PHPDB_ADAPTER_MYSQL_PORT')) {
            $dsn .= ';port=' . getenv('TESTS_PHPDB_ADAPTER_MYSQL_PORT');
        }

        $this->pdo = new PDO(
            $dsn,
            getenv('TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
            getenv('TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
        );
    }

    protected function disconnect(): void
    {
        $this->pdo = null;
    }
}
