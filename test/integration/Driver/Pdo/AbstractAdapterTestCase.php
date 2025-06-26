<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Pdo;
use Laminas\Db\Adapter\SchemaAwareInterface;
use LaminasIntegrationTest\Db\Adapter\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Adapter::class, 'getCurrentSchema')]
#[CoversMethod(AdapterInterface::class, '__construct')]
#[CoversMethod(SchemaAwareInterface::class, 'getCurrentSchema')]
#[CoversMethod(ConnectionInterface::class, 'connect')]
#[CoversMethod(ConnectionInterface::class, 'disconnect')]
#[CoversMethod(ConnectionInterface::class, 'isConnected')]
abstract class AbstractAdapterTestCase extends TestCase
{
    use SetupTrait;

    public function testConnection(): void
    {
        /** @var ConnectionInterface $connection */
        $connection = $this->getAdapter()->getDriver()->getConnection();
        $this->assertInstanceOf(ConnectionInterface::class, $connection);
    }

    public function testGetCurrentSchema(): void
    {
        /** @var AdapterInterface&SchemaAwareInterface $adapter */
        $adapter = $this->getAdapter();
        $schema  = $adapter->getCurrentSchema();
        self::assertIsString($schema);
        self::assertNotEmpty($schema);
    }

    public function testDriverDisconnectAfterQuoteWithPlatform(): void
    {
        $isTcpConnection = $this->isTcpConnection();

        /** @var AdapterInterface $adapter */
        $adapter = $this->getAdapter([
            'db' => [
                'driver' => Pdo::class,
            ],
        ]);
        $adapter->getDriver()->getConnection()->connect();
        self::assertTrue($adapter->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($adapter->getDriver()->getConnection()->isConnected());
        }

        $adapter->getDriver()->getConnection()->disconnect();
        self::assertFalse($adapter->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($adapter->getDriver()->getConnection()->isConnected());
        }

        $adapter->getDriver()->getConnection()->connect();
        self::assertTrue($adapter->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($adapter->getDriver()->getConnection()->isConnected());
        }

        $adapter->getPlatform()->quoteValue('test');

        $adapter->getDriver()->getConnection()->disconnect();

        self::assertFalse($adapter->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($adapter->getDriver()->getConnection()->isConnected());
        }
    }

    protected function isTcpConnection(): bool
    {
        $hostName = $this->getHostname();
        return $hostName !== 'localhost' && $hostName !== '127.0.0.1';
    }
}
