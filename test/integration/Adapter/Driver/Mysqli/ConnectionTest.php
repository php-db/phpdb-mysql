<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysqli\Driver\Mysqli;

use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('integration-mysqli')]
final class ConnectionTest extends TestCase
{
    use TraitSetup;

    public function testConnectionOk(): void
    {
        $connection = new Connection($this->variables);
        $connection->connect();

        self::assertTrue($connection->isConnected());
        $connection->disconnect();
    }
}
