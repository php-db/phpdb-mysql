<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql;

use Laminas\Db\Adapter\AbstractAdapter;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Adapter\Platform\PlatformInterface;

use function is_string;
use function str_starts_with;
use function strtolower;

/**
 * @property DriverInterface $driver
 * @property PlatformInterface $platform
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

        if ($parameters['driver'] instanceof Driver\Mysqli\Mysqli || $parameters['driver'] instanceof Driver\Pdo\Pdo) {
            return $parameters['driver'];
        }

        if (! is_string($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(
                __FUNCTION__
                . ' expects a "driver" to be a string or instance of '
                . Driver\Mysqli\Mysqli::class
                . ' or ' . Driver\Pdo\Pdo::class
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
        $platformName = $parameters['platform'] ?? $this->driver->getDatabasePlatformName();
        // currently only supported by the IbmDb2 & Oracle concrete implementations
        // todo: check recent versions of mysqli and pdo to see if they support this
        $options = $parameters['platform_options'] ?? [];
        // mysqli or pdo_mysql driver
        if ($this->driver instanceof Driver\Mysqli\Mysqli || $this->driver instanceof Driver\Pdo\Pdo) {
            $driver = $this->driver;
        } else {
            $driver = null;
        }

        return new Platform\Mysql($driver);
    }
}
