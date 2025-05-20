<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Driver\Pdo\AbstractPdo;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Laminas\Db\Adapter\Driver\Feature\AbstractFeature;
use Laminas\Db\Adapter\Mysql\DatabasePlatformNameTrait;
use Laminas\Db\Adapter\Profiler;

use function is_array;

class Pdo extends AbstractPdo
{
    use DatabasePlatformNameTrait;

    /**
     * @param array|Connection|\PDO $connection
     * @param string $features
     */
    public function __construct(
        $connection,
        ?Statement $statementPrototype = null,
        ?Result $resultPrototype = null,
        $features = self::FEATURES_DEFAULT
    ) {
        if (! $connection instanceof Connection) {
            $connection = new Connection($connection);
        }

        $this->registerConnection($connection);
        $this->registerStatementPrototype($statementPrototype ?: new Statement());
        $this->registerResultPrototype($resultPrototype ?: new Result());
        if (is_array($features)) {
            foreach ($features as $name => $feature) {
                $this->addFeature($name, $feature);
            }
        } elseif ($features instanceof AbstractFeature) {
            $this->addFeature($features->getName(), $features);
        } elseif ($features === self::FEATURES_DEFAULT) {
            $this->setupDefaultFeatures();
        }
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setProfiler(Profiler\ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
        if ($this->connection instanceof Profiler\ProfilerAwareInterface) {
            $this->connection->setProfiler($profiler);
        }
        if ($this->statementPrototype instanceof Profiler\ProfilerAwareInterface) {
            $this->statementPrototype->setProfiler($profiler);
        }
        return $this;
    }

    /**
     * @return null|Profiler\ProfilerInterface
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * Register connection
     *
     * @return $this Provides a fluent interface
     */
    public function registerConnection(Connection $connection)
    {
        $this->connection = $connection;
        $this->connection->setDriver($this);
        return $this;
    }

    /**
     * Setup the default features for Pdo
     *
     * @return $this Provides a fluent interface
     */
    public function setupDefaultFeatures()
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
}
