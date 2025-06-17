<?php

declare(strict_types=1);

namespace LaminasTest\Db\Adapter\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Connection;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Pdo;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Connection::class, 'getCurrentSchema')]
#[CoversMethod(Connection::class, 'setResource')]
#[CoversMethod(Connection::class, 'getResource')]
#[CoversMethod(Connection::class, 'connect')]
#[CoversMethod(Connection::class, 'isConnected')]
#[CoversMethod(Connection::class, 'disconnect')]
#[CoversMethod(Connection::class, 'beginTransaction')]
#[CoversMethod(Connection::class, 'commit')]
#[CoversMethod(Connection::class, 'rollback')]
#[CoversMethod(Connection::class, 'execute')]
#[CoversMethod(Connection::class, 'prepare')]
#[CoversMethod(Connection::class, 'getLastGeneratedValue')]
#[Group('integration')]
#[Group('integration-pdo')]
final class ConnectionIntegrationTest extends TestCase
{
    /** @var array<string, string> */
    protected array $variables = ['driver' => 'pdo_mysql', 'database' => 'laminas_test'];

    public function testGetCurrentSchema(): void
    {
        $this->markTestIncomplete(
            'Already covered by integration group'
        );
        $connection = new Connection($this->variables);
        self::assertIsString($connection->getCurrentSchema());
    }

    public function testSetResource(): void
    {
        $this->markTestIncomplete(
            'Needs refactored since no Sqlite testing should occur here'
        );
        $resource   = new TestAsset\SqliteMemoryPdo();
        $connection = new Connection([]);
        self::assertSame($connection, $connection->setResource($resource));

        $connection->disconnect();
        unset($connection);
        unset($resource);
    }

    public function testGetResource(): void
    {
        $this->markTestIncomplete(
            'Possibly covered by integration group'
        );
        $connection = new Connection($this->variables);
        $connection->connect();
        self::assertInstanceOf('PDO', $connection->getResource());

        $connection->disconnect();
        unset($connection);
    }

    public function testConnect(): void
    {
        $this->markTestIncomplete(
            'Already covered by integration group'
        );
        $connection = new Connection($this->variables);
        self::assertSame($connection, $connection->connect());
        self::assertTrue($connection->isConnected());

        $connection->disconnect();
        unset($connection);
    }

    public function testIsConnected(): void
    {
        $this->markTestIncomplete(
            'Already covered by integration group'
        );
        $connection = new Connection($this->variables);
        self::assertFalse($connection->isConnected());
        self::assertSame($connection, $connection->connect());
        self::assertTrue($connection->isConnected());

        $connection->disconnect();
        unset($connection);
    }

    public function testDisconnect(): void
    {
        $this->markTestIncomplete(
            'Already covered by integration group'
        );
        $connection = new Connection($this->variables);
        $connection->connect();
        self::assertTrue($connection->isConnected());
        $connection->disconnect();
        self::assertFalse($connection->isConnected());
    }

    /**
     * @todo   Implement testBeginTransaction().
     */
    public function testBeginTransaction(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testCommit().
     */
    public function testCommit(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo   Implement testRollback().
     */
    public function testRollback(): never
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testExecute(): void
    {
        $this->markTestIncomplete(
            'Needs refactored or removed since sqlsrv testing should not occur here'
        );
        $sqlsrv     = new Pdo($this->variables);
        $connection = $sqlsrv->getConnection();

        $result = $connection->execute('SELECT \'foo\'');
        self::assertInstanceOf(Result::class, $result);
    }

    public function testPrepare(): void
    {
        $this->markTestIncomplete(
            'Needs refactored or removed since we do not have a valid connection in Unit test'
        );
        $sqlsrv     = new Pdo($this->variables);
        $connection = $sqlsrv->getConnection();

        $statement = $connection->prepare('SELECT \'foo\'');
        self::assertInstanceOf(Statement::class, $statement);
    }

    public function testGetLastGeneratedValue(): never
    {
        $this->markTestIncomplete('Need to create a temporary sequence.');
        //$connection = new Connection($this->variables);
        //$connection->getLastGeneratedValue();
    }

    #[Group('laminas3469')]
    public function testConnectReturnsConnectionWhenResourceSet(): void
    {
        $this->markTestSkipped(
            'Needs refactored or removed since we do not have a valid connection in Unit test'
        );
        $resource   = new TestAsset\SqliteMemoryPdo();
        $connection = new Connection([]);
        $connection->setResource($resource);
        self::assertSame($connection, $connection->connect());

        $connection->disconnect();
        unset($connection);
        unset($resource);
    }
}
