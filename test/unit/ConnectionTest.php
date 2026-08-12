<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql;

use Exception;
use mysqli;
use Override;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Result;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TypeError;

use const MYSQLI_CLIENT_SSL;
use const MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;

#[RequiresPhpExtension('mysqli')]
#[CoversMethod(Connection::class, 'setDriver')]
#[CoversMethod(Connection::class, 'connect')]
final class ConnectionTest extends TestCase
{
    // fake test-only credential, not a real secret
    // @mago-expect lint:no-literal-password
    private const string TEST_PASSWORD = '1234';

    protected Connection $connection;

    #[Test]
    public function connectionFails(): void
    {
        $mysqli = $this->getMockBuilder(mysqli::class)->getMock();
        $mysqli->expects($this->once())
            ->method('real_connect')
            ->willThrowException(new Exception('simulated connection failure'));

        $connection = $this->createMockConnection($mysqli, []);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(
            'Exception::__construct(): Argument #1 ($message) must be of type string, null given',
        );
        $connection->connect();
    }

    #[Test]
    public function getConnectionParameters(): void
    {
        $this->connection->setConnectionParameters(['foo' => 'bar']);
        static::assertEquals(['foo' => 'bar'], $this->connection->getConnectionParameters());
    }

    #[Test]
    public function nonSecureConnection(): void
    {
        $mysqli = $this->createMockMysqli(0);
        /** @var Connection&MockObject $connection */
        $connection = $this->createMockConnection(
            $mysqli,
            [
                'hostname' => 'localhost',
                'username' => 'superuser',
                'password' => self::TEST_PASSWORD,
                'database' => 'main',
                'port'     => 123,
            ],
        );

        $connection->connect();
    }

    #[Test]
    public function setConnectionParameters(): void
    {
        static::assertEquals($this->connection, $this->connection->setConnectionParameters([]));
    }

    #[Test]
    public function setDriver(): void
    {
        $driver = new Driver($this->connection, new Statement(), new Result());
        static::assertSame($this->connection, $this->connection->setDriver($driver));
    }

    #[Test]
    public function sslConnection(): void
    {
        $mysqli = $this->createMockMysqli(MYSQLI_CLIENT_SSL);
        /** @var Connection&MockObject $connection */
        $connection = $this->createMockConnection(
            $mysqli,
            [
                'hostname' => 'localhost',
                'username' => 'superuser',
                'password' => self::TEST_PASSWORD,
                'database' => 'main',
                'port'     => 123,
                'use_ssl'  => true,
            ],
        );

        $connection->connect();
    }

    #[Test]
    public function sslConnectionNoVerify(): void
    {
        $mysqli = $this->createMockMysqli(MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
        /** @var Connection&MockObject $connection */
        $connection = $this->createMockConnection(
            $mysqli,
            [
                'hostname'       => 'localhost',
                'username'       => 'superuser',
                'password'       => self::TEST_PASSWORD,
                'database'       => 'main',
                'port'           => 123,
                'use_ssl'        => true,
                'driver_options' => [
                    MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT => true,
                ],
            ],
        );

        $connection->connect();
    }

    /**
     * Create a mock connection
     *
     * @param MockObject $mysqli Mock mysqli object
     * @param array      $params Connection params
     */
    protected function createMockConnection(MockObject $mysqli, array $params): MockObject
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['createResource'])
            ->setConstructorArgs([$params])
            ->getMock();
        $connection->expects($this->once())
            ->method('createResource')
            ->willReturn($mysqli);

        return $connection;
    }

    /**
     * Create a mock mysqli
     *
     * @param int $flags Expected flags to real_connect
     */
    protected function createMockMysqli(int $flags): MockObject
    {
        $mysqli = $this->getMockBuilder(mysqli::class)->getMock();
        $mysqli->expects($flags ? $this->once() : $this->never())
            ->method('ssl_set')
            ->with(
                $this->equalTo(''),
                $this->equalTo(''),
                $this->equalTo(''),
                $this->equalTo(''),
                $this->equalTo(''),
            );

        if (0 === $flags) {
            // Do not pass $flags argument if invalid flags provided
            $mysqli->expects($this->once())
                ->method('real_connect')
                ->with(
                    $this->equalTo('localhost'),
                    $this->equalTo('superuser'),
                    $this->equalTo('1234'),
                    $this->equalTo('main'),
                    $this->equalTo(123),
                    $this->equalTo(''),
                )
                ->willReturn(true);
            return $mysqli;
        }

        $mysqli->expects($this->once())
            ->method('real_connect')
            ->with(
                $this->equalTo('localhost'),
                $this->equalTo('superuser'),
                $this->equalTo('1234'),
                $this->equalTo('main'),
                $this->equalTo(123),
                $this->equalTo(''),
                $this->equalTo($flags),
            )
            ->willReturn(true);

        return $mysqli;
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        // if (! (bool) getenv('TESTS_PHPDB_ADAPTER_MYSQL')) {
        //     $this->markTestSkipped('Mysqli test disabled');
        // }
        $this->connection = new Connection([]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void {}
}
