<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver;
use Psr\Container\ContainerInterface;

final class PdoDriverInterfaceFactory
{
    public function __invoke(ContainerInterface $container): PdoDriverInterface&Driver
    {
        /** @var PdoConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $container->get(Connection::class);

        /** @var StatementInterface&Statement $statementInstance */
        $statementInstance = $container->get(Statement::class);

        /** @var ResultInterface&Result $resultInstance */
        $resultInstance = $container->get(Result::class);
        return new Driver(
            $connectionInstance,
            $statementInstance,
            $resultInstance
        );
    }
}
