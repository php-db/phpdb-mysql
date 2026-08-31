<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Override;
use PDO;
use PDOException;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\Pdo\Connection;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversMethod(Connection::class, '__construct')]
#[CoversMethod(Connection::class, 'connect')]
#[CoversMethod(Connection::class, 'getDsnParameter')]
#[CoversMethod(Connection::class, 'getCurrentSchema')]
#[CoversMethod(Connection::class, 'getLastGeneratedValue')]
final class ConnectionTest extends TestCase
{
    protected Connection $connection;

    /** @return array<string, array{string, string, string}> */
    public static function unsafeDsnParameterProvider(): array
    {
        return [
            'dbname appends parameter'      => ['dbname', 'foo;host=attacker.example.com', 'dbname'],
            'host appends parameter'        => ['host', '127.0.0.1;dbname=other', 'host'],
            'charset appends parameter'     => ['charset', 'utf8;dbname=other', 'charset'],
            'unix_socket appends parameter' => ['unix_socket', '/tmp/mysql.sock;dbname=other', 'unix_socket'],
            'version appends parameter'     => ['version', '5.7;dbname=other', 'version'],
            'newline in host'               => ['host', "127.0.0.1\nhost=attacker.example.com", 'host'],
        ];
    }

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
            'version'     => '5.7',
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
        static::assertStringContainsString('version=5.7', $responseString);
    }

    #[Test]
    public function connectReturnsSelfWhenConstructedWithPdoInstance(): void
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');

        $connection = new Connection($pdo);

        static::assertSame($connection, $connection->connect());
        static::assertSame($pdo, $connection->getResource());
    }

    #[Test]
    public function getCurrentSchemaReturnsFalseWhenQueryProducesNoStatement(): void
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->method('query')->willReturn(false);

        $connection = new Connection($pdo);

        static::assertFalse($connection->getCurrentSchema());
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
    public function getLastGeneratedValueReturnsFalseWhenDriverThrows(): void
    {
        $pdo = $this->createStub(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->method('lastInsertId')->willThrowException(new PDOException('driver does not support lastInsertId'));

        $connection = new Connection($pdo);

        static::assertFalse($connection->getLastGeneratedValue());
    }

    #[Test]
    public function getLastGeneratedValueReturnsFalseWithoutResource(): void
    {
        static::assertFalse($this->connection->getLastGeneratedValue());
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
