<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Mysql\Driver;
use Laminas\Db\Container\AdapterManager;
use Laminas\Db\Adapter\Exception\RuntimeException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Psr\Container\ContainerInterface;

final class DriverInterfaceFactory
{
    public function __invoke(ContainerInterface $container): DriverInterface
    {
        $manager = $container->get(AdapterManager::class);
        return $this->getDriver($manager);
    }

    private function getDriver(AdapterManager $manager): DriverInterface
    {
        $config = $manager->get('db');
        // Got to have this to determine the driver type

        return match (strtolower($config['driver'])) {
            'pdo_mysql',
            'pdomysql',
            'pdo'    => new Driver\Pdo\Pdo($manager->get(ConnectionInterface::class)),
            'mysqli' => new Driver\Mysqli\Mysqli($manager->get(ConnectionInterface::class)),
            default  => throw new ServiceNotCreatedException(
                'Driver type can not be determined from provided driver: ' . $config['driver']
            ),
        };
    }
}
