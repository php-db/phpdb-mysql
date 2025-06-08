<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Driver\DriverAwareInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\Feature\AbstractFeature;
use Laminas\Db\Adapter\Driver\Pdo\AbstractPdo;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Mysql\DatabasePlatformNameTrait;
use Laminas\Db\Adapter\Profiler\ProfilerAwareInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;

use function is_array;

class Pdo extends AbstractPdo
{
    use DatabasePlatformNameTrait;

    /**
     * @return $this Provides a fluent interface
     */
    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        if ($this->connection instanceof ProfilerAwareInterface) {
            $this->connection->setProfiler($profiler);
        }
        if ($this->statementPrototype instanceof ProfilerAwareInterface) {
            $this->statementPrototype->setProfiler($profiler);
        }
        return $this;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * Register connection
     *
     * @return $this Provides a fluent interface
     */
    public function registerConnection(ConnectionInterface $connection): DriverInterface
    {
        $this->connection = $connection;
        if ($this->connection instanceof DriverAwareInterface) {
            $this->connection->setDriver($this);
        }
        return $this;
    }

    /**
     * Setup the default features for Pdo
     *
     * @return $this Provides a fluent interface
     */
    public function setupDefaultFeatures(): static
    {
        return $this;
    }

    /**
     * Get feature
     *
     * @param string $name
     * @return AbstractFeature|false
     */
    public function getFeature($name)
    {
        if (isset($this->features[$name])) {
            return $this->features[$name];
        }
        return false;
    }

    /**
     * @param \PDOStatement $resource
     */
    public function createResult($resource, $context = null): ResultInterface
    {
        /** @var Result */
        $result   = clone $this->resultPrototype;
        $rowCount = null;

        $result->initialize($resource, $this->connection->getLastGeneratedValue(), $rowCount);
        return $result;
    }
}
