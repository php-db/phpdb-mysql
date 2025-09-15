<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Container\AdapterManager;
use PhpDb\Container\DriverInterfaceFactoryFactoryInterface as FactoryFactoryInterface;
use Psr\Container\ContainerInterface;

use function sprintf;

final class DriverInterfaceFactoryFactory implements FactoryFactoryInterface
{
    public function __invoke(
        ?ContainerInterface $container = null,
        ?string $requestedName = null
    ): callable {
        $adapterConfig = $container->get('config')['db']['adapters'] ?? [];
        if (! isset($adapterConfig[$requestedName]['driver'])) {
            throw new \RuntimeException(sprintf(
                'Named adapter "%s" is not configured with a driver',
                $requestedName
            ));
        }
        $adapterServices = $container->get('config')[AdapterManager::class];

        $configuredDriver = $adapterConfig[$requestedName]['driver'];
        $aliasTo ??= $adapterServices['aliases'][$configuredDriver] ?? $configuredDriver;
        $driverFactory = $adapterServices['factories'][$aliasTo];
        return new $driverFactory();
    }
}
