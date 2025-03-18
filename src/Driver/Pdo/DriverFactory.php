<?php

declare(strict_types=1);

namespace Laminas\Db\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class DriverFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DriverInterface
    {
        return new Driver($container->get('config')['db']);
    }
}
