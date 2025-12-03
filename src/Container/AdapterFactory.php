<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\ResultSet\ResultSetInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

/** @internal */
final class AdapterFactory
{
    public function __invoke(ContainerInterface $container): AdapterInterface
    {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        if (! isset($dbConfig['driver'])) {
            throw new RuntimeException('Database driver configuration is missing.');
        }

        /** @var string $driver */
        $driver = $dbConfig['driver'];

        if (! $container->has($driver)) {
            throw new ServiceNotFoundException(sprintf(
                'Database driver "%s" is not registered in the adapter manager.',
                $driver
            ));
        }

        /** @var DriverInterface|PdoDriverInterface $driverInstance */
        $driverInstance = $container->get($driver);

        if (! $container->has(PlatformInterface::class)) {
            throw new ServiceNotFoundException(sprintf(
                'Database platform "%s" is not registered in the adapter manager.',
                PlatformInterface::class
            ));
        }

        /** @var PlatformInterface $platformInstance */
        $platformInstance = $container->get(PlatformInterface::class);

        if (! $container->has(ResultSetInterface::class)) {
            throw new ServiceNotFoundException(sprintf(
                'ResultSet "%s" is not registered in the adapter manager.',
                ResultSetInterface::class
            ));
        }

        /** @var ResultSetInterface $resultSetInstance */
        $resultSetInstance = $container->get(ResultSetInterface::class);

        /** @var ProfilerInterface|null $profilerInstanceOrNull */
        $profilerInstanceOrNull = $container->has(ProfilerInterface::class)
                ? $container->get(ProfilerInterface::class)
                : null;

        return new Adapter(
            $driverInstance,
            $platformInstance,
            $resultSetInstance,
            $profilerInstanceOrNull
        );
    }
}
