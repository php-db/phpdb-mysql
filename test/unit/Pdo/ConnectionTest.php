<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Override;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\Pdo\Connection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Connection::class, 'connect')]
final class ConnectionTest extends TestCase
{
    protected Connection $connection;

    #[Test]
    #[Group('2622')]
    public function arrayOfConnectionParametersCreatesCorrectDsn(): void
    {
        $connection = new Connection([
            'driver'      => 'pdo_mysql',
            'charset'     => 'utf8',
            'dbname'      => 'foo',
            'port'        => '3306',
            'unix_socket' => '/var/run/mysqld/mysqld.sock',
        ]);
        try {
            $connection->connect();
        } catch (InvalidConnectionParametersException|RuntimeException) {
            // connection failure is expected/ignored here; only dsn construction is under test
            // @mago-expect lint:no-empty-catch-clause
        }
        $responseString = $connection->getDsn();

        static::assertStringStartsWith('mysql:', $responseString);
        static::assertStringContainsString('charset=utf8', $responseString);
        static::assertStringContainsString('dbname=foo', $responseString);
        static::assertStringContainsString('port=3306', $responseString);
        static::assertStringContainsString('unix_socket=/var/run/mysqld/mysqld.sock', $responseString);
    }

    /**
     * Test getConnectedDsn returns a DSN string if it has been set
     */
    #[Test]
    public function getDsn(): void
    {
        $dsn = 'mysql:';
        $this->connection->setConnectionParameters(['dsn' => $dsn]);
        try {
            $this->connection->connect();
        } catch (InvalidConnectionParametersException|RuntimeException) {
            // connection failure is expected/ignored here; only dsn construction is under test
            // @mago-expect lint:no-empty-catch-clause
        }
        $responseString = $this->connection->getDsn();

        static::assertEquals($dsn, $responseString);
    }

    #[Test]
    public function hostnameAndUnixSocketThrowsInvalidConnectionParametersException(): void
    {
        $this->expectException(InvalidConnectionParametersException::class);
        $this->expectExceptionMessage(
            'Ambiguous connection parameters, both hostname and unix_socket parameters were set',
        );

        $connection = new Connection([
            'driver'      => 'pdo_mysql',
            'host'        => '127.0.0.1',
            'dbname'      => 'foo',
            'port'        => '3306',
            'unix_socket' => '/var/run/mysqld/mysqld.sock',
        ]);
        $connection->connect();
    }

    /**
     * Test getResource method tries to connect to  the database, it should never return null
     */
    #[Test]
    public function resource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->connection->getResource();
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->connection = new Connection([]);
    }
}
