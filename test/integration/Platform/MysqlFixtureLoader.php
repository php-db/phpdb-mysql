<?php

namespace LaminasIntegrationTest\Db\Mysql\Platform;

use Exception;
use PDO;

use function file_get_contents;
use function getenv;
use function print_r;
use function sprintf;

final class MysqlFixtureLoader implements FixtureLoader
{
    /** @var string */
    private $fixtureFile = __DIR__ . '/../TestFixtures/mysql.sql';

    /** @var ?PDO */
    private $pdo;

    public function createDatabase(): void
    {
        $this->connect();

        if (
            false === $this->pdo->exec(sprintf(
                "CREATE DATABASE IF NOT EXISTS %s",
                getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE')
            ))
        ) {
            throw new Exception(sprintf(
                "I cannot create the MySQL %s test database: %s",
                getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE'),
                print_r($this->pdo->errorInfo(), true)
            ));
        }

        $this->pdo->exec('USE ' . getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE'));

        if (false === $this->pdo->exec(file_get_contents($this->fixtureFile))) {
            throw new Exception(sprintf(
                "I cannot create the table for %s database. Check the %s file. %s ",
                getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE'),
                $this->fixtureFile,
                print_r($this->pdo->errorInfo(), true)
            ));
        }

        $this->disconnect();
    }

    public function dropDatabase(): void
    {
        $this->connect();

        $this->pdo->exec(sprintf(
            "DROP DATABASE IF EXISTS %s",
            getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE')
        ));

        $this->disconnect();
    }

    protected function connect(): void
    {
        $dsn = 'mysql:host=' . getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_HOSTNAME');
        if (getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_PORT')) {
            $dsn .= ';port=' . getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_PORT');
        }

        $this->pdo = new PDO(
            $dsn,
            getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_USERNAME'),
            getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_PASSWORD')
        );
    }

    protected function disconnect(): void
    {
        $this->pdo = null;
    }
}
