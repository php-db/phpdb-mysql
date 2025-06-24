<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Connection;
use Psr\Container\ContainerInterface;

final class PdoConnectionFactory
{
    public function __invoke(ContainerInterface $container): ConnectionInterface&Connection
    {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $connectionConfig */
        $connectionConfig = $dbConfig['connection'] ?? [];

        return new Connection($connectionConfig);
    }
}
