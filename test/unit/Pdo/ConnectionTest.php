<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Exception;
use Override;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\Pdo\Connection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Connection::class, 'getResource')]
#[CoversMethod(Connection::class, 'getDsn')]
final class ConnectionTest extends TestCase
{
    protected Connection $connection;

    #[Group('2622')]
    public function testArrayOfConnectionParametersCreatesCorrectDsn(): void
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
        } catch (Exception) {
        }
        $responseString = $connection->getDsn();

        self::assertStringStartsWith('mysql:', $responseString);
        self::assertStringContainsString('charset=utf8', $responseString);
        self::assertStringContainsString('dbname=foo', $responseString);
        self::assertStringContainsString('port=3306', $responseString);
        self::assertStringContainsString('unix_socket=/var/run/mysqld/mysqld.sock', $responseString);
    }

    /**
     * Test getConnectedDsn returns a DSN string if it has been set
     */
    public function testGetDsn(): void
    {
        $dsn = 'mysql:';
        $this->connection->setConnectionParameters(['dsn' => $dsn]);
        try {
            $this->connection->connect();
        } catch (Exception) {
        }
        $responseString = $this->connection->getDsn();

        self::assertEquals($dsn, $responseString);
    }

    public function testHostnameAndUnixSocketThrowsInvalidConnectionParametersException(): void
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
    public function testResource(): void
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
