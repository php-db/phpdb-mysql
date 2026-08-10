<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Pdo;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Adapter::class, 'getCurrentSchema')]
#[CoversMethod(Adapter::class, '__construct')]
#[CoversMethod(Connection::class, 'connect')]
#[CoversMethod(Connection::class, 'disconnect')]
#[CoversMethod(Connection::class, 'isConnected')]
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
        /** @var AdapterInterface&SchemaAwareInterface&Adapter $adapter */
        $adapter = $this->getAdapter();
        $schema  = $adapter->getCurrentSchema();
        self::assertIsString($schema);
        self::assertNotEmpty($schema);
    }

    public function testDriverDisconnectAfterQuoteWithPlatform(): void
    {
        $isTcpConnection = $this->isTcpConnection();

        /** @var AdapterInterface&Adapter $adapter */
        $adapter = $this->getAdapter([
            'db' => [
                'driver' => Driver::class,
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
