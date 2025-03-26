<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Mysqli;

use Laminas\Db\Mysql\Driver\Mysqli\Connection;

use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
#[Attributes\CoversMethod(Connection::class, 'connect')]
#[Attributes\CoversMethod(Connection::class, 'disconnect')]
final class ConnectionTest extends TestCase
{
    use TraitSetup;

    public function testConnection(): void
    {
        $connection = new Connection($this->variables);
        $connection->connect();

        self::assertTrue($connection->isConnected());
        $connection->disconnect();
        self::assertFalse($connection->isConnected());
    }
}
