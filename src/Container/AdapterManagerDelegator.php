<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use PhpDb\Adapter\Mysql\ConfigProvider;
use PhpDb\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class AdapterManagerDelegator implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
        ?array $options = null
    ): AdapterManager {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $callback();
        $adapterManager->configure(
            (new ConfigProvider())->getAdapterManagerConfig()
        );

        return $adapterManager;
    }
}
