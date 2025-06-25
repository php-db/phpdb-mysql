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

use function getmypid;
use function shell_exec;

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
            self::assertTrue($this->isConnectedTcp());
        }

        $adapter->getDriver()->getConnection()->disconnect();

        self::assertFalse($adapter->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($this->isConnectedTcp());
        }

        $adapter->getDriver()->getConnection()->connect();

        self::assertTrue($adapter->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($this->isConnectedTcp());
        }

        $adapter->getPlatform()->quoteValue('test');

        $adapter->getDriver()->getConnection()->disconnect();

        self::assertFalse($adapter->getDriver()->getConnection()->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($this->isConnectedTcp());
        }
    }

    protected function isConnectedTcp(): bool
    {
        $mypid = getmypid();
        /** @var array $config */
        $config = $this->getConfig();
        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];
        /** @var array $connectionConfig */
        $connectionConfig = $dbConfig['connection'] ?? [];
        /** @var string $dbPort */
        $dbPort = (string) $connectionConfig['port'] ?? '3306';
        /** @psalm-suppress ForbiddenCode - running lsof */
        $lsof = shell_exec("lsof -i -P -n | grep $dbPort | grep $mypid");

        return $lsof !== null;
    }

    protected function isTcpConnection(): bool
    {
        return $this->getHostname() !== 'localhost';
    }
}
