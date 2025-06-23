<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Exception\RuntimeException;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Laminas\Db\Container\AdapterManager;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

use function sprintf;

final class AdapterFactory
{
    public function __invoke(ContainerInterface $container): AdapterInterface
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);

        /** @var array $config */
        $config = $container->get('config');

        if (! isset($config['db']['driver'])) {
            throw new RuntimeException('Database driver configuration is missing.');
        }

        if (! $adapterManager->has($config['db']['driver'])) {
            throw new ServiceNotFoundException(sprintf(
                'Database driver "%s" is not registered in the adapter manager.',
                $config['db']['driver']
            ));
        }

        return new Adapter(
            $adapterManager->get($config['db']['driver']),
            $adapterManager->get(PlatformInterface::class),
            $adapterManager->get(ResultSetInterface::class),
            $adapterManager->has(ProfilerInterface::class)
                ? $adapterManager->get(ProfilerInterface::class)
                : null
        );
    }
}
