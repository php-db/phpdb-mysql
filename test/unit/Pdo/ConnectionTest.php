<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Override;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\Pdo\Connection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversMethod(Connection::class, 'connect')]
#[CoversMethod(Connection::class, 'getDsnParameter')]
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

    #[Test]
    #[DataProvider('unsafeDsnParameterProvider')]
    public function rejectsConnectionParameterContainingDsnControlCharacters(
        string $parameter,
        string $value,
        string $reportedParameter,
    ): void {
        $this->expectException(InvalidConnectionParametersException::class);
        $this->expectExceptionMessage(
            sprintf('The "%s" connection parameter contains invalid characters', $reportedParameter),
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
