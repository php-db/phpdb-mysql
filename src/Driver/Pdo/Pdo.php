<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Driver\Pdo\AbstractPdo;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Mysql\DatabasePlatformNameTrait;
use Override;
use PDOStatement;

class Pdo extends AbstractPdo
{
    use DatabasePlatformNameTrait;

    /**
     * @param PDOStatement $resource
     */
    #[Override]
    public function createResult($resource): ResultInterface
    {
        /** @var Result $result */
        $result   = clone $this->resultPrototype;
        $rowCount = null;

        $result->initialize($resource, $this->connection->getLastGeneratedValue(), $rowCount);
        return $result;
    }
}
