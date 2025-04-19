<?php

namespace Laminas\Db\Adapter\Mysql;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Adapter\AbstractAdapter;
use Laminas\Db\Adapter\Exception;

use function is_string;
use function str_starts_with;
use function strtolower;

/**
 * @property Driver\DriverInterface $driver
 * @property Platform\PlatformInterface $platform
 */
class Adapter extends AbstractAdapter
{
    public function getCurrentSchema(): string
    {
        return $this->driver->getConnection()->getCurrentSchema();
    }

    protected function createDriver(array $parameters): DriverInterface
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
                $driver = new Driver\Mysqli\Mysqli($parameters, null, null, $options);
                break;
            case 'pdo':
            default:
                if ($driverName === 'pdo' || str_starts_with($driverName, 'pdo')) {
                    $driver = new Driver\Pdo\Pdo($parameters);
                }
        }

        if (! isset($driver) || ! $driver instanceof DriverInterface) {
            throw new Exception\InvalidArgumentException('DriverInterface expected');
        }

        return $driver;
    }

    protected function createPlatform(array $parameters): PlatformInterface
    {
        if (isset($parameters['platform'])) {
            $platformName = $parameters['platform'];
        } elseif ($this->driver instanceof DriverInterface) {
            $platformName = $this->driver->getDatabasePlatformName();
        } else {
            throw new Exception\InvalidArgumentException(
                'A platform could not be determined from the provided configuration'
            );
        }

        // currently only supported by the IbmDb2 & Oracle concrete implementations
        //$options = $parameters['platform_options'] ?? [];

        // mysqli or pdo_mysql driver
        if ($this->driver instanceof Driver\Mysqli\Mysqli || $this->driver instanceof Driver\Pdo\Pdo) {
            $driver = $this->driver;
        } else {
            $driver = null;
        }

        return new Platform\Mysql($driver);
    }
}
