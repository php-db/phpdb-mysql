<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Result;
use PhpDb\Mysql\Statement;
use Psr\Container\ContainerInterface;

final class DriverInterfaceFactory
{
    public function __invoke(
        ContainerInterface&ServiceManager $container,
        string $requestedName,
        ?array $options = null
    ): DriverInterface&Driver {
        if (! isset($options['connection'])) {
            throw ContainerException::forService(
                Driver::class,
                self::class,
                '$options["connection"] must contain an array of connection configuration.'
            );
        }

        /** @var Driver\ConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $container->build(Connection::class, $options);

        /** @var Driver\StatementInterface&Statement $statementInstance */
        $statementInstance = $container->build(
            Statement::class,
            $options['options'] ?? []
        );

        /** @var Driver\ResultInterface&Result $resultInstance */
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
