<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Laminas\Db\Adapter\Driver\PdoDriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Connection;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Pdo as PdoDriver;
use Laminas\Db\Container\AdapterManager;
use Psr\Container\ContainerInterface;

final class PdoDriverFactory
{
    public function __invoke(ContainerInterface $container): PdoDriverInterface&PdoDriver
    {
        /** @var AdapterManager $adapterManager */
        $adapterManager = $container->get(AdapterManager::class);

        /** @var ConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $adapterManager->get(Connection::class);

        /** @var StatementInterface&Statement $statementInstance */
        $statementInstance = $adapterManager->get(Statement::class);

        /** @var ResultInterface&Result $resultInstance */
        $resultInstance = $adapterManager->get(Result::class);

        return new PdoDriver(
            $connectionInstance,
            $statementInstance,
            $resultInstance
        );
    }
}
