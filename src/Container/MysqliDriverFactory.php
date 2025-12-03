<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver;
use PhpDb\Adapter\Mysql\Driver\Mysqli;
use Psr\Container\ContainerInterface;

/** @internal */
final class MysqliDriverFactory
{
    public function __invoke(ContainerInterface $container): Driver\DriverInterface&Mysqli\Mysqli
    {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $options */
        $options = $dbConfig['options'] ?? [];

        /** @var Driver\ConnectionInterface&Mysqli\Connection $connectionInstance */
        $connectionInstance = $container->get(Mysqli\Connection::class);

        /** @var Driver\StatementInterface&Mysqli\Statement $statementInstance */
        $statementInstance = $container->get(Mysqli\Statement::class);

        /** @var Driver\ResultInterface&Mysqli\Result $resultInstance */
        $resultInstance = $container->has(Mysqli\Result::class) ? $container->get(Mysqli\Result::class) : null;

        return new Mysqli\Mysqli(
            connection: $connectionInstance,
            statementPrototype: $statementInstance,
            resultPrototype: $resultInstance ?? new Mysqli\Result(),
            options: $options
        );
    }

    public static function createFromConfig(
        ContainerInterface $container,
        string $requestedName,
    ): Driver\DriverInterface&Mysqli\Mysqli {
        $connectionFactory = (
            $container->get(ConnectionInterfaceFactoryFactory::class)
        )($container, $requestedName);
        /** @var array $config */
        $config = $container->get('config');
        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];
        /** @var array $adapterConfig */
        $adapterConfig = $dbConfig['adapters'][$requestedName] ?? [];

        return new Mysqli\Mysqli(
            $connectionFactory::createFromConfig($container, $requestedName),
            $container->get(Mysqli\Statement::class),
            $container->get(Mysqli\Result::class),
            $adapterConfig['options'] ?? []
        );
    }
}
