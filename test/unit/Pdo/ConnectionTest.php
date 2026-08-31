<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Exception;
use Override;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\Pdo\Connection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversMethod(Connection::class, 'getResource')]
#[CoversMethod(Connection::class, 'getDsn')]
#[CoversMethod(Connection::class, 'getDsnParameter')]
final class ConnectionTest extends TestCase
{
    protected Connection $connection;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->connection = new Connection([]);
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
     * Test getConnectedDsn returns a DSN string if it has been set
     */
    public function testGetDsn(): void
    {
        $dsn = "mysql:";
        $this->connection->setConnectionParameters(['dsn' => $dsn]);
        try {
            $this->connection->connect();
        } catch (Exception) {
        }
        $responseString = $this->connection->getDsn();

        self::assertEquals($dsn, $responseString);
    }

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

    public function testHostnameAndUnixSocketThrowsInvalidConnectionParametersException(): void
    {
        $this->expectException(InvalidConnectionParametersException::class);
        $this->expectExceptionMessage(
            'Ambiguous connection parameters, both hostname and unix_socket parameters were set'
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

    #[DataProvider('unsafeDsnParameterProvider')]
    public function testRejectsConnectionParameterContainingDsnControlCharacters(
        string $parameter,
        string $value,
        string $reportedParameter
    ): void {
        $this->expectException(InvalidConnectionParametersException::class);
        $this->expectExceptionMessage(
            sprintf('The "%s" connection parameter contains invalid characters', $reportedParameter)
        );

        $connection = new Connection([
            'driver'   => 'pdo_mysql',
            $parameter => $value,
        ]);
        $connection->connect();
    }

    /** @return array<string, array{string, string, string}> */
    public static function unsafeDsnParameterProvider(): array
    {
        return [
            'dbname appends parameter'      => ['dbname', 'foo;host=attacker.example.com', 'dbname'],
            'host appends parameter'        => ['host', '127.0.0.1;dbname=other', 'host'],
            'charset appends parameter'     => ['charset', 'utf8;dbname=other', 'charset'],
            'unix_socket appends parameter' => ['unix_socket', '/tmp/mysql.sock;dbname=other', 'unix_socket'],
            'newline in host'               => ['host', "127.0.0.1\nhost=attacker.example.com", 'host'],
        ];
    }
}
