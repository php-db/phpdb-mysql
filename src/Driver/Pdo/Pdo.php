<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Driver\Pdo;

use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Mysql\DatabasePlatformNameTrait;

class Pdo extends AbstractPdo
{
    use DatabasePlatformNameTrait;

    /**
     * @param PDOStatement $resource
     */
    #[Override]
    public function createResult($resource): ResultInterface
    {
        /** @var ResultInterface&Result $result */
        $result = clone $this->resultPrototype;

        $rowCount = 0;

        $lastGeneratedValue = $this->getLastGeneratedValue();

        $result->initialize($resource, $lastGeneratedValue, $rowCount);
        return $result;
    }
}
