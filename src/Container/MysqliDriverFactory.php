<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver;
use PhpDb\Adapter\Mysql\Driver\Mysqli;
use PhpDb\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class MysqliDriverFactory
{
    public function __invoke(ContainerInterface $container): Driver\DriverInterface&Mysqli\Mysqli
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);

        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $options */
        $options = $dbConfig['options'] ?? [];

        /** @var Driver\ConnectionInterface&Mysqli\Connection $connectionInstance */
        $connectionInstance = $adapterManager->get(Mysqli\Connection::class);

        /** @var Driver\StatementInterface&Mysqli\Statement $statementInstance */
        $statementInstance = $adapterManager->get(Mysqli\Statement::class);

        /** @var Driver\ResultInterface&Mysqli\Result $resultInstance */
        $resultInstance = $adapterManager->get(Mysqli\Result::class);

        return new Mysqli\Mysqli(
            $connectionInstance,
            $statementInstance,
            $resultInstance,
            $options
        );
    }
}
