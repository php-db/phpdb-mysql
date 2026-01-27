<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Mysql\Driver\Pdo\Connection;
use PhpDb\Adapter\Mysql\Driver\Pdo\Pdo as PdoDriver;
use Psr\Container\ContainerInterface;

final class PdoDriverFactory
{
    public function __invoke(ContainerInterface $container): PdoDriverInterface&PdoDriver
    {
        /** @var ConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $container->get(Connection::class);

        /** @var StatementInterface&Statement $statementInstance */
        $statementInstance = $container->get(Statement::class);

        /** @var ResultInterface&Result $resultInstance */
        $resultInstance = $container->get(Result::class);
        return new PdoDriver(
            $connectionInstance,
            $statementInstance,
            $resultInstance
        );
    }
}
