<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Driver\Pdo;

use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Mysql\DatabasePlatformNameTrait;

final class Pdo extends AbstractPdo
{
    use DatabasePlatformNameTrait;

    /**
     * @param PDOStatement $resource
     */
    #[Override]
    public function createResult($resource): ResultInterface
    {
        /** @var Result $result */
        $result = clone $this->resultPrototype;

        /** @var null $rowCount */
        $rowCount = null;

        /** @var string|int|bool|null $lastGeneratedValue */
        $lastGeneratedValue = $this->getLastGeneratedValue();

        $result->initialize($resource, $lastGeneratedValue, $rowCount);
        return $result;
    }
}
