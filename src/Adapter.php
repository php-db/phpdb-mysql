<?php

declare(strict_types=1);

namespace Laminas\Db\Mysql;

use Laminas\Db\Adapter\AbstractAdapter;
use Laminas\Db\Adapter\Profiler;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Platform;
use Laminas\Db\Exception;
use Laminas\Db\ResultSet;

/**
 * @property DriverInterface $driver
 * @property Platform\PlatformInterface $platform
 */
class Adapter extends AbstractAdapter
{
    /** @var DriverInterface */
    protected $driver;

    /** @var Platform\PlatformInterface */
    protected $platform;

    /**
     * @deprecated
     *
     * @var Driver\StatementInterface
     */
    protected $lastPreparedStatement;
    /**
     * @param DriverInterface|array $driver
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        array $parameters,
        DriverInterface $driver,
        Platform\PlatformInterface $platform,
        ?ResultSet\ResultSetInterface $queryResultPrototype = null,
        ?Profiler\ProfilerInterface $profiler = null
    ) {

        if ($parameters !== []) {
            if ($profiler === null && isset($parameters['profiler'])) {
                $profiler = $this->createProfiler($parameters);
            }
        }

        $driver->checkEnvironment();
        $this->driver = $driver;

        $this->platform                = $platform;
        $this->queryResultSetPrototype = $queryResultPrototype ?: new ResultSet\ResultSet();

        if ($profiler) {
            $this->setProfiler($profiler);
        }
    }
}
