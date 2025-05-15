<?php

declare(strict_types=1);

namespace LaminasTest\Db\TestAsset;

use Laminas\Db\Adapter\Mysql\Driver\Pdo\Connection;
use LaminasTest\Db\Adapter\Pdo\PdoStubDriver;

/**
 * Test asset class used only by {@see \LaminasTest\Db\Adapter\Driver\Pdo\ConnectionTransactionsTest}
 */
final class ConnectionWrapper extends Connection
{
    public function __construct()
    {
        $this->resource = new PdoStubDriver('foo', 'bar', 'baz');
    }

    public function getNestedTransactionsCount(): int
    {
        return $this->nestedTransactionsCount;
    }
}
