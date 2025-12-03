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

        /** @var (StatementInterface&Statement)|null $statementInstance */
        $statementInstance = $container->has(Statement::class) ? $container->get(Statement::class) : null;

        /** @var (ResultInterface&Result)|null $resultInstance */
        $resultInstance = $container->has(Result::class) ? $container->get(Result::class) : null;

        return new PdoDriver(
            connection: $connectionInstance,
            statementPrototype: $statementInstance ?? new Statement(),
            resultPrototype: $resultInstance ?? new Result(),
        );
    }

    public static function createFromConfig(
        ContainerInterface $container,
        string $requestedName,
    ): PdoDriverInterface&PdoDriver {
        $connectionFactory = (
            $container->get(ConnectionInterfaceFactoryFactory::class)
        )($container, $requestedName);
        /** @var array $config */
        $config = $container->get('config');
        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];
        /** @var array $adapterConfig */
        $adapterConfig = $dbConfig['adapters'][$requestedName] ?? [];

        /** @var ConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $connectionFactory::createFromConfig($container, $requestedName);

        /** @var (StatementInterface&Statement)|null $statementInstance */
        $statementInstance = $container->has(Statement::class) ? $container->get(Statement::class) : null;

        /** @var (ResultInterface&Result)|null $resultInstance */
        $resultInstance = $container->has(Result::class) ? $container->get(Result::class) : null;
        return new PdoDriver(
            $connectionInstance,
            $statementInstance ?? new Statement(),
            $resultInstance ?? new Result(),
        );
    }
}
