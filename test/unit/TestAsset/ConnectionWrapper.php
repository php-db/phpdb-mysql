<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\TestAsset;

use PhpDb\Mysql\Pdo\Connection;

/**
 * Test asset class used only by {@see \PhpDbTest\Mysql\Driver\Pdo\ConnectionTransactionsTest}
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
