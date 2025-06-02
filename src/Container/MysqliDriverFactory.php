<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli;
use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class MysqliDriverFactory
{
    public function __invoke(ContainerInterface $container): DriverInterface
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);
        $options        = $container->get('config')['db']['options'] ?? [];
        return new Mysqli\Mysqli(
            $adapterManager->get(Mysqli\Connection::class),
            $adapterManager->get(Mysqli\Statement::class),
            $adapterManager->get(Mysqli\Result::class),
            $options
        );
    }
}
