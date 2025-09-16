<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Driver\Pdo;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Mysql\Driver\Pdo\Pdo;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDbIntegrationTest\Adapter\Mysql\Container\TestAsset\SetupTrait;
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
