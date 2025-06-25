<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Driver\AbstractConnection;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\Pdo\AbstractPdoConnection;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Laminas\Db\Adapter\Driver\PdoConnectionInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Connection;
use LaminasIntegrationTest\Db\Adapter\Mysql\Container\TestAsset\SetupTrait;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('integration-pdo')]
#[CoversClass(Connection::class)]
#[CoversMethod(Connection::class, 'prepare')]
#[CoversClass(ConnectionInterface::class)]
#[CoversMethod(ConnectionInterface::class, 'execute')]
#[CoversMethod(ConnectionInterface::class, 'getResource')]
#[CoversMethod(ConnectionInterface::class, 'getLastGeneratedValue')]
final class ConnectionTest extends TestCase
{
    use SetupTrait;

    public function testSetResource(): void
    {
        $resource = new PDO('sqlite::memory:');
        /** @var Connection $connection */
        $connection = $this->getAdapter()->getDriver()->getConnection();
        self::assertSame($connection, $connection->setResource($resource));
    }

    public function testGetResource(): void
    {
        $connection = $this->getAdapter()->getDriver()->getConnection();
        self::assertInstanceOf(PDO::class, $connection->getResource());
    }

    public function testExecute(): void
    {
        $connection = $this->getAdapter()->getDriver()->getConnection();
        /** @var ResultInterface&Result $result */
        $result = $connection->execute('SELECT \'foo\'');
        self::assertInstanceOf(ResultInterface::class, $result);
        self::assertInstanceOf(Result::class, $result);
    }

    public function testPrepare(): void
    {
        /** @var ConnectionInterface&PdoConnectionInterface&AbstractConnection&AbstractPdoConnection&Connection $connection */
        $connection = $this->getAdapter()->getDriver()->getConnection();
        /** @var StatementInterface&Statement $statement */
        $statement = $connection->prepare('SELECT \'foo\'');
        self::assertInstanceOf(StatementInterface::class, $statement);
        self::assertInstanceOf(Statement::class, $statement);
    }

    public function testGetLastGeneratedValue(): void
    {
        /** @var ConnectionInterface&PdoConnectionInterface&AbstractConnection&AbstractPdoConnection&Connection $connection */
        $connection = $this->getAdapter()->getDriver()->getConnection();
        $connection->connect();
        $lastId = (int) $connection->getLastGeneratedValue();
        self::assertIsInt($lastId);
        $connection->disconnect();
    }

    public function testConnectMethodReturnsConnectionInterface(): void
    {
        /** @var ConnectionInterface&PdoConnectionInterface&AbstractConnection&AbstractPdoConnection&Connection $connection */
        $connection = $this->getAdapter()->getDriver()->getConnection();
        self::assertInstanceOf(ConnectionInterface::class, $connection->connect());
        $connection->disconnect();
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
}
