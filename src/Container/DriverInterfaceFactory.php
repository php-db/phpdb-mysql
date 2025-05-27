<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class DriverInterfaceFactory
{
    use InterfaceFactoryTrait;

    public function __invoke(ContainerInterface $container): DriverInterface
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);
        return $this->getDriver($adapterManager);
    }
}
