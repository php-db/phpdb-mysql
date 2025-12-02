<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Driver\Pdo;

use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverAwareInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Mysql\DatabasePlatformNameTrait;

class Pdo extends AbstractPdo
{
    use DatabasePlatformNameTrait;

    public function __construct(
        protected AbstractPdoConnection|\PDO $connection,
        protected StatementInterface&PdoDriverAwareInterface $statementPrototype = new Statement(),
        protected ResultInterface $resultPrototype = new Result(),
        array $features = [],
    ) {
        parent::__construct(
            $connection,
            $statementPrototype,
            $resultPrototype,
            $features
        );
    }

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
