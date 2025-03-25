<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Mysql\Adapter;
use PHPUnit\Framework\TestCase;

use function getmypid;
use function shell_exec;

abstract class AbstractAdapterTestCase extends TestCase
{
    /** @var ?int */
    public const DB_SERVER_PORT = null;

    protected AdapterInterface&Adapter $adapter;

    /**
     * @covers \Laminas\Db\Adapter\Adapter::__construct()
     */
    public function testConnection(): void
    {
        $this->assertInstanceOf(Adapter::class, $this->adapter);
    }

    public function testDriverDisconnectAfterQuoteWithPlatform(): void
    {
        $isTcpConnection = $this->isTcpConnection();

        $driver     = $this->adapter->getDriver();
        $connection = $driver->getConnection();
        $platform   = $this->adapter->getPlatform();

        $connection->connect();

        self::assertTrue($connection->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($this->isConnectedTcp());
        }

        $connection->disconnect();

        self::assertFalse($connection->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($this->isConnectedTcp());
        }

        $connection->connect();

        self::assertTrue($connection->isConnected());
        if ($isTcpConnection) {
            self::assertTrue($this->isConnectedTcp());
        }

        $platform->quoteValue('test');

        $connection->disconnect();

        self::assertFalse($connection->isConnected());
        if ($isTcpConnection) {
            self::assertFalse($this->isConnectedTcp());
        }
    }

    protected function isConnectedTcp(): bool
    {
        $mypid  = getmypid();
        $dbPort = static::DB_SERVER_PORT;
        /** @psalm-suppress ForbiddenCode */
        $lsof   = shell_exec("lsof -i -P -n | grep $dbPort | grep $mypid");

        return $lsof !== null;
    }

    protected function isTcpConnection(): bool
    {
        return $this->getHostname() !== 'localhost';
    }

    abstract protected function getHostname(): array|string|false;
}
