<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql;

use PhpDb\Mysql\Connection;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('integration-mysqli')]
#[CoversMethod(Connection::class, 'connect')]
#[CoversMethod(Connection::class, 'disconnect')]
#[CoversMethod(Connection::class, 'isConnected')]
final class ConnectionTest extends TestCase
{
    use SetupTrait;

    #[Test]
    public function connectionOk(): void
    {
        /** @var array $config */
        $config = ['db' => ['driver' => 'Mysqli']];
        /** @var Connection $connection */
        $connection = $this->getAdapter($config)->getDriver()->getConnection();
        $connection->connect();
        static::assertTrue($connection->isConnected());
        $connection->disconnect();
        static::assertFalse($connection->isConnected());
    }
}
