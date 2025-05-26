<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\ConfigInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Mysql\Adapter;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class AdapterFactory
{
    public function __invoke(ContainerInterface $container): Adapter
    {
        // $adapter = new Adapter($container->get('config')['db']);
        // $adapter->setDriver($container->get('db_driver'));
        // $adapter->setPlatform($container->get('db_platform'));
        $manager = $container->get(AdapterManager::class);
        $config  = $manager->get(ConfigInterface::class);
        return new Adapter(
            $manager->get(DriverInterface::class),
            $manager->get(PlatformInterface::class),
            null, // needs added to AdapterManager allowed services
            $manager->get(ProfilerInterface::class)
        );
    }
}
