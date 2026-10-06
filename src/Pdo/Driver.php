<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Pdo;

use Override;
use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\PdoDriverAwareInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

final class Driver extends AbstractPdo
{
    protected ?ProfilerInterface $profiler = null;

    /**
     * @param array<string, mixed> $features
     */
    // @mago-expect analysis:unused-parameter
    public function __construct(
        (PdoConnectionInterface&PdoDriverAwareInterface)|PDO $connection,
        StatementInterface&PdoDriverAwareInterface $statementPrototype = new Statement(),
        ResultInterface $resultPrototype = new Result(),
        array $features = [],
    ) {
        $this->connection         = $connection;
        $this->statementPrototype = $statementPrototype;
        $this->resultPrototype    = $resultPrototype;

        if (! $this->connection instanceof PDO) {
            $this->connection->setDriver($this);
        }

        $this->statementPrototype->setDriver($this);
    }

    /**
     * @param PDOStatement $resource
     */
    #[Override]
    public function createResult($resource): ResultInterface
    {
        /** @var ResultInterface&Result $result */
        $result = clone $this->resultPrototype;

        $rowCount = null;

        $lastGeneratedValue = $this->getLastGeneratedValue();

        $result->initialize($resource, $lastGeneratedValue, $rowCount);
        return $result;
    }
}
