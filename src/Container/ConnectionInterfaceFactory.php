<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Exception\RuntimeException;
use Laminas\Db\Container\AdapterManager;
use Laminas\Db\Adapter\Mysql\Driver;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Psr\Container\ContainerInterface;

use function strtolower;

final class ConnectionInterfaceFactory
{
    public function __invoke(
        ContainerInterface $container
    ): ConnectionInterface {
        $manager    = $container->get(AdapterManager::class);
        $connection = $this->getConnection($manager->get('db'));
        return $connection;
    }

    private function getConnection(array $config): ConnectionInterface
    {
        // Got to have this to determine the driver type
        return match(strtolower($config['driver'])) {
            'pdo_mysql',
            'pdomysql',
            'pdo'    => new Driver\Pdo\Connection($config),
            'mysqli' => new Driver\Mysqli\Connection($config),
            default  => throw new ServiceNotCreatedException(
                'Connection type can not be determined from provided driver: ' . $config['driver']
            ),
        };
    }
}
