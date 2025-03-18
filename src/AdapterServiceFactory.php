<?php

namespace Laminas\Db\Mysql;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Profiler\ProfilerInterface;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdapterServiceFactory implements FactoryInterface
{
    /**
     * Create db adapter service
     *
     * @param string $requestedName
     * @param array $options
     * @return Adapter
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');

        return new Adapter(
            $config['db'],
            $container->get(DriverInterface::class),
            $container->get(PlatformInterface::class),
            $container->has(ResultSetInterface::class) ? $container->get(ResultSetInterface::class) : null,
            $container->has(ProfilerInterface::class) ? $container->get(ProfilerInterface::class) : null
        );
    }
}
