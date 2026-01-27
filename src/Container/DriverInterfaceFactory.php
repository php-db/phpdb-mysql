<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Result;
use PhpDb\Mysql\Statement;
use Psr\Container\ContainerInterface;

final class DriverInterfaceFactory
{
    public function __invoke(ContainerInterface $container): DriverInterface&Driver
    {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $options */
        $options = $dbConfig['options'] ?? [];

        /** @var Driver\ConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $container->get(Connection::class);

        /** @var Driver\StatementInterface&Statement $statementInstance */
        $statementInstance = $container->get(Statement::class);
        /** @var Driver\ResultInterface&Result $resultInstance */
        $resultInstance = $container->get(Result::class);

        return new Driver(
            $connectionInstance,
            $statementInstance,
            $resultInstance,
            $options
        );
    }
}
