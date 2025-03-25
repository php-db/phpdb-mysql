<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Mysql\Platform;

use Exception;
use PDO;
use Webmozart\Assert\Assert;

use function file_get_contents;
use function getenv;
use function print_r;
use function sprintf;

/** @api */
final class MysqlFixtureLoader implements FixtureLoader
{
    private string $fixtureFile = __DIR__ . '/../TestFixtures/mysql.sql';

    /**
     *
     * @var ?PDO
     */
    private ?PDO $pdo = null;

    public function createDatabase(): void
    {
        $this->connect();

        /** @var string $database */
        $database = getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE');
        if (false === $this->pdo->exec("CREATE DATABASE IF NOT EXISTS $database")) {
            throw new Exception(sprintf(
                "I cannot create the MySQL %s test database: %s",
                $database,
                print_r($this->pdo->errorInfo(), true)
            ));
        }

        $this->pdo->exec("USE $database");
        $sql = file_get_contents($this->fixtureFile);
        Assert::notFalse($sql, 'The SQL must be a string');
        Assert::stringNotEmpty($sql, 'The SQL must be a string');
        if (false === $this->pdo->exec($sql)) {
            throw new Exception(sprintf(
                "I cannot create the table for %s database. Check the %s file. %s ",
                $database,
                $this->fixtureFile,
                print_r($this->pdo->errorInfo(), true)
            ));
        }

        $this->disconnect();
    }

    public function dropDatabase(): void
    {
        $this->connect();

        $database = getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE');
        Assert::string($database, 'The database must be a string');
        $this->pdo->exec("DROP DATABASE IF EXISTS $database");

        $this->disconnect();
    }

    protected function connect(): void
    {
        $dsn = 'mysql:host=';
        $hostname = getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_HOSTNAME');
        Assert::string($hostname, 'The hostname must be a string');
        $dsn .= $hostname;

        if (getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_PORT') !== false) {
            $port = getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_PORT');
            Assert::string($port, 'The port must be a string');
            $dsn .= ';port=' . $port;
        }
        $username = getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_USERNAME');
        Assert::string($username, 'The username must be a string');
        $password = getenv('TESTS_LAMINAS_DB_MYSQL_ADAPTER_PASSWORD');
        Assert::string($password, 'The password must be a string');
        $this->pdo = new PDO(
            $dsn,
            $username,
            $password
        );
    }

    protected function disconnect(): void
    {
        $this->pdo = null;
    }
}
