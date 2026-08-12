<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Mysqli;

use mysqli;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Result;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function getenv;

#[Group('integration')]
#[Group('integration-mysqli')]
#[CoversClass(Connection::class)]
final class ConnectionTest extends TestCase
{
    #[Test]
    public function beginTransactionAndCommit(): void
    {
        $connection = $this->createConnection();

        $connection->beginTransaction();
        $connection->commit();

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function beginTransactionAndRollback(): void
    {
        $connection = $this->createConnection();

        $connection->beginTransaction();
        $connection->rollback();

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function connectAndDisconnect(): void
    {
        $connection = new Connection($this->connectionParameters());

        static::assertFalse($connection->isConnected());

        $connection->connect();
        static::assertTrue($connection->isConnected());

        $connection->disconnect();
        static::assertFalse($connection->isConnected());
    }

    #[Test]
    public function constructWithMysqliResource(): void
    {
        $connection = new Connection($this->createMysqli());

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function executeInsertReturnsGeneratedValue(): void
    {
        $connection = $this->createConnection();

        $connection->execute('INSERT INTO test (name, value) VALUES (\'generated\', \'value\')');

        static::assertIsInt($connection->getLastGeneratedValue());

        $connection->execute('DELETE FROM test WHERE name = \'generated\'');
    }

    #[Test]
    public function executeSelect(): void
    {
        $connection = $this->createConnection();

        $result = $connection->execute('SELECT * FROM test WHERE id = 1');

        static::assertNotNull($result);
        static::assertTrue($result->isBuffered());
        static::assertTrue($result->isQueryResult());
        static::assertSame(3, $result->getFieldCount());
        static::assertSame(1, $result->count());
    }

    #[Test]
    public function getCurrentSchema(): void
    {
        $connection = $this->createConnection();

        static::assertSame(
            (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
            $connection->getCurrentSchema(),
        );
    }

    #[Test]
    public function rollbackWithoutTransactionThrows(): void
    {
        $connection = $this->createConnection();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Must call beginTransaction() before you can rollback.');
        $connection->rollback();
    }

    /**
     * @return array{hostname: string, username: string, password: string, database: string, port: int, charset: string}
     */
    private function connectionParameters(): array
    {
        $host = (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME');
        if ('' === $host) {
            $host = 'localhost';
        }

        $port = (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PORT');
        $port = '' === $port ? 3306 : (int) $port;

        return [
            'hostname' => $host,
            'username' => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
            'password' => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
            'database' => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
            'port'     => $port,
            'charset'  => 'utf8',
        ];
    }

    private function createConnection(): Connection
    {
        $connection = new Connection($this->createMysqli());
        new Driver($connection, new Statement(), new Result());

        return $connection;
    }

    private function createMysqli(): mysqli
    {
        $parameters = $this->connectionParameters();

        return new mysqli(
            $parameters['hostname'],
            $parameters['username'],
            $parameters['password'],
            $parameters['database'],
            $parameters['port'],
        );
    }
}
