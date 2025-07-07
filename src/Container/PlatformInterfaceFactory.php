<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use mysqli;
use PDO;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Mysql\Platform\Mysql;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Container\AdapterManager;
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
