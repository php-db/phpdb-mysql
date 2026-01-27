<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use mysqli;
use PDO;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Mysql\AdapterPlatform;
use Psr\Container\ContainerInterface;

final class PlatformInterfaceFactory
{
    public function __invoke(ContainerInterface $container): PlatformInterface&AdapterPlatform
    {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var string $driver */
        $driver = $dbConfig['driver'];

        /** @var DriverInterface|mysqli|PDO $driverInstance */
        $driverInstance = $container->get($driver);

        return new AdapterPlatform($driverInstance);
    }
}
