<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo\TestAsset;

use PDO;

final class PdoStubDriver extends PDO
{
    public function beginTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    /**
     * @param string $user
     * @param string $password
     * @phpstan-ignore constructor.unusedParameter, constructor.unusedParameter, constructor.unusedParameter
     */
    public function __construct(string $dsn, $user, $password)
    {
    }

    public function rollBack(): bool
    {
        return true;
    }
}
