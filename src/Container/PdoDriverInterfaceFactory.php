<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver;
use Psr\Container\ContainerInterface;

final class PdoDriverInterfaceFactory
{
    public function __invoke(
        ContainerInterface&ServiceManager $container,
        string $requestedName,
        ?array $options = null
    ): DriverInterface&Driver {
        if (! isset($options['connection'])) {
            throw ContainerException::forService(
                \PhpDb\Mysql\Driver::class,
                self::class,
                '$options["connection"] must containe an array of connection configuration.'
            );
        }

        /** @var ConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $container->build(Connection::class, $options);

        /** @var StatementInterface&Statement $statementInstance */
        $statementInstance = $container->build(
            Statement::class,
            $options['options'] ?? []
        );

        /** @var ResultInterface&Result $resultInstance */
        $resultInstance = $container->has(ResultInterface::class)
            ? $container->get(ResultInterface::class)
            : new Result();

        return new Driver(
            $connectionInstance,
            $statementInstance,
            $resultInstance,
            $options
        );
    }
}
