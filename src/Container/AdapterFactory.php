<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Mysql\Adapter;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Laminas\Db\Container\AdapterManager;
use Laminas\Db\ResultSet\ResultSetInterface;
use Psr\Container\ContainerInterface;

final class AdapterFactory
{
    public function __invoke(ContainerInterface $container): Adapter
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);
        return new Adapter(
            $adapterManager->get(DriverInterface::class),
            $adapterManager->get(PlatformInterface::class),
            $adapterManager->get(ResultSetInterface::class),
            $adapterManager->get(ProfilerInterface::class)
        );
    }
}
