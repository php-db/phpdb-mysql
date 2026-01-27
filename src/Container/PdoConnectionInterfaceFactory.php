<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Mysql\Pdo\Connection;
use Psr\Container\ContainerInterface;

final class PdoConnectionInterfaceFactory
{
    public function __invoke(ContainerInterface $container): PdoConnectionInterface&Connection
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
