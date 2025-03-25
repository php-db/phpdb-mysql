<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Mysqli;

use Laminas\Db\Mysql\Driver\Mysqli\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 * @group integration-mysqli
 */
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
