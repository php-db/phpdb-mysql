<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo\TestAsset;

use Override;
use PDO;
use PDOStatement;

final class CtorlessPdo extends PDO
{
    public function __construct(
        protected PDOStatement $mockStatement,
    ) {}

    /**
     * @param array<array-key, mixed> $options
     */
    #[Override]
    public function prepare(string $query, $options = null): PDOStatement
    {
        return $this->mockStatement;
    }
}
