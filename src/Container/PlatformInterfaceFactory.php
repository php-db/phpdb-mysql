<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Mysql\Platform\Mysql;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Container\AdapterManager;
use mysqli;
use PDO;
use Psr\Container\ContainerInterface;

final class PlatformInterfaceFactory
{
    public function __invoke(ContainerInterface $container): PlatformInterface&Mysql
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);

        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var string $driver */
        $driver = $dbConfig['driver'];

        /** @var DriverInterface|mysqli|PDO $driverInstance */
        $driverInstance = $adapterManager->get($driver);

        return new Mysql($driverInstance);
    }
}
