<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Adapter\Profiler;
use Laminas\Db\Container\AdapterManager;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\Container\ContainerInterface;

final class AdapterManagerDelegator implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
        ?array $options = null
    ): AdapterManager {
        $adapterManager = $callback();
        $adapterManager->configure([
            'aliases'   => [
                Profiler\ProfilerInterface::class => Profiler\Profiler::class,
            ],
            'factories' => [
                AdapterInterface::class    => AdapterFactory::class,
                ConnectionInterface::class => ConnectionInterfaceFactory::class,
                DriverInterface::class     => DriverInterfaceFactory::class,
                PlatformInterface::class   => PlatformInterfaceFactory::class,
                Profiler\Profiler::class   => InvokableFactory::class,
                ResultInterface::class     => ResultInterfaceFactory::class,
            ],
        ]);

        return $adapterManager;
    }
}
