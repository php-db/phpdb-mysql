<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Adapter\Mysql\Platform\Mysql;
use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class PlatformInterfaceFactory
{
    public function __invoke(ContainerInterface $container): PlatformInterface&Mysql
    {
        /** @var AdapterManager $manager */
        $adapterManager = $container->get(AdapterManager::class);
        /** @var DriverInterface $driver */
        $driver = $container->get('config')['db']['driver'];
        return new Mysql($adapterManager->get($driver));
    }
}
