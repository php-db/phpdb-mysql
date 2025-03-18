<?php

namespace Laminas\Db\Mysql;

use InvalidArgumentException;
use Laminas\Db\Adapter\AbstractAdapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Platform;
use Laminas\Db\Adapter\Profiler;
use Laminas\Db\Driver\DriverInterface;
use Laminas\Db\Driver\Pdo\Pdo;
use Laminas\Db\Driver\ResultInterface;
use Laminas\Db\Exception;
use Laminas\Db\ResultSet;

use function func_get_args;
use function in_array;
use function is_array;
use function is_bool;
use function is_string;
use function strpos;
use function strtolower;

/**
 * @property DriverInterface $driver
 * @property Platform\PlatformInterface $platform
 */
class Adapter extends AbstractAdapter
{
    /**
     * Query Mode Constants
     */
    public const QUERY_MODE_EXECUTE = 'execute';
    public const QUERY_MODE_PREPARE = 'prepare';

    /**
     * Prepare Type Constants
     */
    public const PREPARE_TYPE_POSITIONAL = 'positional';
    public const PREPARE_TYPE_NAMED      = 'named';

    public const FUNCTION_FORMAT_PARAMETER_NAME = 'formatParameterName';
    public const FUNCTION_QUOTE_IDENTIFIER      = 'quoteIdentifier';
    public const FUNCTION_QUOTE_VALUE           = 'quoteValue';

    public const VALUE_QUOTE_SEPARATOR = 'quoteSeparator';

    /** @var DriverInterface */
    protected $driver;

    /** @var Platform\PlatformInterface */
    protected $platform;

    /** @var Profiler\ProfilerInterface */
    protected $profiler;

    /** @var ResultSet\ResultSetInterface */
    protected $queryResultSetPrototype;

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
        $driver,
        ?Platform\PlatformInterface $platform = null,
        ?ResultSet\ResultSetInterface $queryResultPrototype = null,
        ?Profiler\ProfilerInterface $profiler = null
    ) {
        // first argument can be an array of parameters
        $parameters = [];

        if (is_array($driver)) {
            $parameters = $driver;
            if ($profiler === null && isset($parameters['profiler'])) {
                $profiler = $this->createProfiler($parameters);
            }
            $driver = $this->createDriver($parameters);
        } elseif (! $driver instanceof DriverInterface) {
            throw new Exception\InvalidArgumentException(
                'The supplied or instantiated driver object does not implement ' . DriverInterface::class
            );
        }

        $driver->checkEnvironment();
        $this->driver = $driver;

        if ($platform === null) {
            $platform = $this->createPlatform($parameters);
        }

        $this->platform                = $platform;
        $this->queryResultSetPrototype = $queryResultPrototype ?: new ResultSet\ResultSet();

        if ($profiler) {
            $this->setProfiler($profiler);
        }
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setProfiler(Profiler\ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
        if ($this->driver instanceof Profiler\ProfilerAwareInterface) {
            $this->driver->setProfiler($profiler);
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
     * getDriver()
     *
     * @throws Exception\RuntimeException
     * @return DriverInterface
     */
    public function getDriver()
    {
        if ($this->driver === null) {
            throw new Exception\RuntimeException('Driver has not been set or configured for this adapter.');
        }
        return $this->driver;
    }

    /**
     * @return Platform\PlatformInterface
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return ResultSet\ResultSetInterface
     */
    public function getQueryResultSetPrototype()
    {
        return $this->queryResultSetPrototype;
    }

    /** @return string */
    public function getCurrentSchema()
    {
        return $this->driver->getConnection()->getCurrentSchema();
    }

    /**
     * query() is a convenience function
     *
     * @param string $sql
     * @param string|array|ParameterContainer $parametersOrQueryMode
     * @throws Exception\InvalidArgumentException
     * @return Driver\StatementInterface|ResultSet\ResultSet
     */
    public function query(
        $sql,
        $parametersOrQueryMode = self::QUERY_MODE_PREPARE,
        ?ResultSet\ResultSetInterface $resultPrototype = null
    ) {
        if (
            is_string($parametersOrQueryMode)
            && in_array($parametersOrQueryMode, [self::QUERY_MODE_PREPARE, self::QUERY_MODE_EXECUTE])
        ) {
            $mode       = $parametersOrQueryMode;
            $parameters = null;
        } elseif (is_array($parametersOrQueryMode) || $parametersOrQueryMode instanceof ParameterContainer) {
            $mode       = self::QUERY_MODE_PREPARE;
            $parameters = $parametersOrQueryMode;
        } else {
            throw new Exception\InvalidArgumentException(
                'Parameter 2 to this method must be a flag, an array, or ParameterContainer'
            );
        }

        if ($mode === self::QUERY_MODE_PREPARE) {
            $lastPreparedStatement = $this->driver->createStatement($sql);
            $lastPreparedStatement->prepare();
            if (is_array($parameters) || $parameters instanceof ParameterContainer) {
                if (is_array($parameters)) {
                    $lastPreparedStatement->setParameterContainer(new ParameterContainer($parameters));
                } else {
                    $lastPreparedStatement->setParameterContainer($parameters);
                }
                $result = $lastPreparedStatement->execute();
            } else {
                return $lastPreparedStatement;
            }
        } else {
            $result = $this->driver->getConnection()->execute($sql);
        }

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet     = $resultPrototype ?? $this->queryResultSetPrototype;
            $resultSetCopy = clone $resultSet;

            $resultSetCopy->initialize($result);

            return $resultSetCopy;
        }

        return $result;
    }

    /**
     * Create statement
     *
     * @param  string $initialSql
     * @param  null|ParameterContainer|array $initialParameters
     * @return Driver\StatementInterface
     */
    public function createStatement($initialSql = null, $initialParameters = null)
    {
        $statement = $this->driver->createStatement($initialSql);
        if (
            $initialParameters === null
            || ! $initialParameters instanceof ParameterContainer
            && is_array($initialParameters)
        ) {
            $initialParameters = new ParameterContainer(is_array($initialParameters) ? $initialParameters : []);
        }
        $statement->setParameterContainer($initialParameters);
        return $statement;
    }

    public function getHelpers()
    {
        $functions = [];
        $platform  = $this->platform;
        foreach (func_get_args() as $arg) {
            switch ($arg) {
                case self::FUNCTION_QUOTE_IDENTIFIER:
                    $functions[] = function ($value) use ($platform) {
                        return $platform->quoteIdentifier($value);
                    };
                    break;
                case self::FUNCTION_QUOTE_VALUE:
                    $functions[] = function ($value) use ($platform) {
                        return $platform->quoteValue($value);
                    };
                    break;
            }
        }
    }

    /**
     * @param string $name
     * @throws Exception\InvalidArgumentException
     * @return DriverInterface|Platform\PlatformInterface
     */
    // public function __get($name)
    // {
    //     switch (strtolower($name)) {
    //         case 'driver':
    //             return $this->driver;
    //         case 'platform':
    //             return $this->platform;
    //         default:
    //             throw new Exception\InvalidArgumentException('Invalid magic property on adapter');
    //     }
    // }

    /**
     * @param array $parameters
     * @return DriverInterface
     * @throws InvalidArgumentException
     * @throws Exception\InvalidArgumentException
     */
    protected function createDriver($parameters)
    {
        if (! isset($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(
                __FUNCTION__ . ' expects a "driver" key to be present inside the parameters'
            );
        }

        if ($parameters['driver'] instanceof DriverInterface) {
            return $parameters['driver'];
        }

        if (! is_string($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(
                __FUNCTION__ . ' expects a "driver" to be a string or instance of DriverInterface'
            );
        }

        $options = [];
        if (isset($parameters['options'])) {
            $options = (array) $parameters['options'];
            unset($parameters['options']);
        }

        $driverName = strtolower($parameters['driver']);
        switch ($driverName) {
            case 'mysqli':
                $driver = new Driver\Mysqli\Driver($parameters, null, null, $options);
                break;
            case 'pdo':
            default:
                if ($driverName === 'pdo' || strpos($driverName, 'pdo') === 0) {
                    $driver = new Pdo($parameters);
                }
        }

        if (! isset($driver) || ! $driver instanceof DriverInterface) {
            throw new Exception\InvalidArgumentException('DriverInterface expected');
        }

        return $driver;
    }

    /**
     * todo: replace with factory
     * @param array $parameters
     * @return Platform\PlatformInterface
     */
    protected function createPlatform(array $parameters)
    {
        if (isset($parameters['platform'])) {
            $platformName = $parameters['platform'];
        } elseif ($this->driver instanceof DriverInterface) {
            $platformName = $this->driver->getDatabasePlatformName(DriverInterface::NAME_FORMAT_CAMELCASE);
        } else {
            throw new Exception\InvalidArgumentException(
                'A platform could not be determined from the provided configuration'
            );
        }

        // currently only supported by the IbmDb2 & Oracle concrete implementations
        $options = $parameters['platform_options'] ?? [];


        // todo: replace with factory
        switch ($platformName) {
            case 'Mysql':
                // mysqli or pdo_mysql driver
                if ($this->driver instanceof Driver\Mysqli\Driver || $this->driver instanceof Pdo) {
                    $driver = $this->driver;
                } else {
                    $driver = null;
                }
                return new Platform\Mysql($driver);
            default:
                return new Platform\Sql92();
        }
    }

    /**
     * todo: remains in abstract adapter
     * @param array $parameters
     * @return Profiler\ProfilerInterface
     * @throws Exception\InvalidArgumentException
     */
    // protected function createProfiler($parameters)
    // {
    //     if ($parameters['profiler'] instanceof Profiler\ProfilerInterface) {
    //         return $parameters['profiler'];
    //     }

    //     if (is_bool($parameters['profiler'])) {
    //         return $parameters['profiler'] === true ? new Profiler\Profiler() : null;
    //     }

    //     throw new Exception\InvalidArgumentException(
    //         '"profiler" parameter must be an instance of ProfilerInterface or a boolean'
    //     );
    // }
}
