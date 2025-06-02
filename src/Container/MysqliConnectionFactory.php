<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Connection;
use Psr\Container\ContainerInterface;

final class MysqliConnectionFactory
{
    public function __invoke(ContainerInterface $container): ConnectionInterface
    {
        $dbConfig = $container->get('config')['db']['connection'] ?? [];

        return new Connection($dbConfig);
    }
}
