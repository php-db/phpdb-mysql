<?php

declare(strict_types=1);

namespace Laminas\Db\Mysql\Driver\Mysqli;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class DriverFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DriverInterface
    {
        // todo: be a little more defensive here
        $dbConfig = $container->get('config')['db'];
        $options  = [];
        if (isset($dbConfig['options'])) {
            $options = (array) $dbConfig['options'];
            unset($dbConfig['options']);
        }
        return new Driver($dbConfig, null, null, $options);
    }
}
