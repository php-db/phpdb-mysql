<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\PdoDriverInterface;
use Laminas\Db\Adapter\Driver\Pdo;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Connection;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Pdo as PdoDriver;
use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class PdoDriverFactory
{
    public function __invoke(ContainerInterface $container): PdoDriverInterface
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);
        return new PdoDriver(
            $adapterManager->get(Connection::class),
            $adapterManager->get(Pdo\Statement::class),
            $adapterManager->get(Pdo\Result::class)
        );
    }
}
