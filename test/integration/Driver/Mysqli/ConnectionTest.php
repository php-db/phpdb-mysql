<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Driver\Mysqli;

use PhpDb\Adapter\Mysql\Driver\Mysqli\Connection;
use PhpDbIntegrationTest\Adapter\Mysql\Container\TestAsset\SetupTrait;
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
        $config = ['db' => ['driver' => 'Mysqli']];
        /** @var Connection $connection */
        $connection = $this->getAdapter($config)->getDriver()->getConnection();
        $connection->connect();
        self::assertTrue($connection->isConnected());
        $connection->disconnect();
        self::assertFalse($connection->isConnected());
    }
}
