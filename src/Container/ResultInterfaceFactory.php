<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class ResultInterfaceFactory
{
    use InterfaceFactoryTrait;

    public function __invoke(ContainerInterface $container): ResultInterface
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);
        return $this->getResult($adapterManager);
    }
}
