<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Mysqli;

use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Connection;
use LaminasIntegrationTest\Db\Adapter\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('integration-mysqli')]
#[CoversMethod(Connection::class, 'connect')]
#[CoversMethod(Connection::class, 'disconnect')]
#[CoversMethod(Connection::class, 'isConnected')]
final class ConnectionTest extends TestCase
{
    use SetupTrait;

    public function testConnectionOk(): void
    {
        /** @var array $config */
        $config = $this->getConfig();

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $connectionConfig */
        $connectionConfig = $dbConfig['connection'] ?? [];

        $connection = new Connection($connectionConfig);
        $connection->connect();

        self::assertTrue($connection->isConnected());
        $connection->disconnect();
        self::assertFalse($connection->isConnected());
    }
}
