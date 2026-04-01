<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Pdo;

use Override;
use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\Feature\DriverFeatureProviderInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdo;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\PdoDriverAwareInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Mysql\DatabasePlatformNameTrait;

class Driver extends AbstractPdo
{
    use DatabasePlatformNameTrait;

    public function __construct(
        protected PdoConnectionInterface|PDO $connection,
        protected StatementInterface&PdoDriverAwareInterface $statementPrototype,
        protected ResultInterface $resultPrototype,
        array $features = [],
    ) {
        if ($this->connection instanceof PdoDriverAwareInterface) {
            $this->connection->setDriver($this);
        }

        $this->statementPrototype->setDriver($this);

        // $features is not constructor promoted because $this->features is defined in the trait
        if ($features !== [] && $this instanceof DriverFeatureProviderInterface) {
            $this->addFeatures($features);
        }
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
