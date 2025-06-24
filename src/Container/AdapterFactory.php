<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\PdoDriverInterface;
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

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        if (! isset($dbConfig['driver'])) {
            throw new RuntimeException('Database driver configuration is missing.');
        }

        /** @var string $driver */
        $driver = $dbConfig['driver'];

        if (! $adapterManager->has($driver)) {
            throw new ServiceNotFoundException(sprintf(
                'Database driver "%s" is not registered in the adapter manager.',
                $driver
            ));
        }

        /** @var DriverInterface|PdoDriverInterface $driverInstance */
        $driverInstance = $adapterManager->get($driver);

        if (! $adapterManager->has(PlatformInterface::class)) {
            throw new ServiceNotFoundException(sprintf(
                'Database platform "%s" is not registered in the adapter manager.',
                PlatformInterface::class
            ));
        }

        /** @var PlatformInterface $platformInstance */
        $platformInstance = $adapterManager->get(PlatformInterface::class);

        if (! $adapterManager->has(ResultSetInterface::class)) {
            throw new ServiceNotFoundException(sprintf(
                'ResultSet "%s" is not registered in the adapter manager.',
                ResultSetInterface::class
            ));
        }

        /** @var ResultSetInterface $resultSetInstance */
        $resultSetInstance = $adapterManager->get(ResultSetInterface::class);

        /** @var ProfilerInterface|null $profilerInstanceOrNull */
        $profilerInstanceOrNull = $adapterManager->has(ProfilerInterface::class)
                ? $adapterManager->get(ProfilerInterface::class)
                : null;

        return new Adapter(
            $driverInstance,
            $platformInstance,
            $resultSetInstance,
            $profilerInstanceOrNull
        );
    }
}
