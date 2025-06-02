<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Mysql\Driver;
use Laminas\Db\Container\AdapterManager;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

use function strtolower;

trait InterfaceFactoryTrait
{
    private function getConnection(AdapterManager $adapterManager): ConnectionInterface
    {
        $config = $adapterManager->get('db');

        if ($this->isPdo($config['driver'])) {
            return new Driver\Pdo\Connection($config);
        }
        return new Driver\Mysqli\Connection($config);
    }

    // todo: pull the ResultInterface from the adapter manager and pass it to the driver
    private function getDriver(AdapterManager $adapterManager): DriverInterface
    {
        $config = $adapterManager->get('db');

        if ($this->isPdo($config['driver'])) {
            return new Driver\Pdo\Pdo(
                $adapterManager->get(ConnectionInterface::class)
            );
        }
        // return new Driver\Mysqli\Mysqli(
        //     $adapterManager->get(ConnectionInterface::class)
        // );
    }

    private function getResult(AdapterManager $adapterManager): ResultInterface
    {
        $config = $adapterManager->get('db');

        if ($this->isPdo($config['driver'])) {
            return new Result();
        }
        return new Driver\Mysqli\Result();
    }

    private function isPdo(string $driver): bool
    {
        return match (strtolower($driver)) {
            'pdo_mysql',
            'pdomysql',
            'pdo'    => true,
            'mysqli' => false,
            default  => throw new ServiceNotCreatedException(
                'Driver type can not be determined from provided driver: ' . $driver
            ),
        };
    }
}
