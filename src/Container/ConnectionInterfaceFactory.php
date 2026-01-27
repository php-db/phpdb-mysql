<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Mysql\Connection;
use Psr\Container\ContainerInterface;

final class ConnectionInterfaceFactory
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): ConnectionInterface&Connection {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $connectionConfig */
        $connectionConfig = $dbConfig['connection'] ?? [];

        return new Connection($connectionConfig);
    }

    public static function createFromConfig(
        ContainerInterface $container,
        string $requestedName
    ): ConnectionInterface&Connection {
        $adapterConfig = $container->get('config')['db']['adapters'][$requestedName] ?? [];
        return new Connection($adapterConfig['connection'] ?? []);
    }
}
