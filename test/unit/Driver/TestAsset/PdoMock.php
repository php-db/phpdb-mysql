<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Driver\TestAsset;

use PDO;

/**
 * Stub class
 */
final class PdoMock extends PDO
{
    public function __construct()
    {
    }

    public function beginTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    public function rollBack(): bool
    {
        return true;
    }
}
