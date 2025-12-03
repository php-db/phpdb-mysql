<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver;
use PhpDb\Adapter\Mysql\Driver\Mysqli;
use Psr\Container\ContainerInterface;
use RuntimeException;

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

        $hasStatement = $container->has(Mysqli\Statement::class);
        $hasResult    = $container->has(Mysqli\Result::class);

        return match (true) {
            ! $hasStatement && ! $hasResult => new Mysqli\Mysqli(
                connection: $connectionInstance,
                options: $options,
            ),
            $hasStatement && ! $hasResult => new Mysqli\Mysqli(
                connection: $connectionInstance,
                statementPrototype: $container->get(Mysqli\Statement::class),
                options: $options,
            ),
            ! $hasStatement && $hasResult => new Mysqli\Mysqli(
                connection: $connectionInstance,
                resultPrototype: $container->get(Mysqli\Result::class),
                options: $options,
            ),
            $hasStatement && $hasResult => new Mysqli\Mysqli(
                connection: $connectionInstance,
                statementPrototype: $container->get(Mysqli\Statement::class),
                resultPrototype: $container->get(Mysqli\Result::class),
                options: $options,
            ),
            default => throw new RuntimeException('Unable to create PdoDriver from configuration.'),
        };
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

        /** @var Driver\ConnectionInterface&Mysqli\Connection $connectionInstance */
        $connectionInstance = $connectionFactory::createFromConfig($container, $requestedName);

        $hasStatement = $container->has(Mysqli\Statement::class);
        $hasResult    = $container->has(Mysqli\Result::class);

        return match (true) {
            ! $hasStatement && ! $hasResult => new Mysqli\Mysqli(
                connection: $connectionInstance,
                options: $adapterConfig['options'] ?? [],
            ),
            $hasStatement && ! $hasResult => new Mysqli\Mysqli(
                connection: $connectionInstance,
                statementPrototype: $container->get(Mysqli\Statement::class),
                options: $adapterConfig['options'] ?? [],
            ),
            ! $hasStatement && $hasResult => new Mysqli\Mysqli(
                connection: $connectionInstance,
                resultPrototype: $container->get(Mysqli\Result::class),
                options: $adapterConfig['options'] ?? [],
            ),
            $hasStatement && $hasResult => new Mysqli\Mysqli(
                connection: $connectionInstance,
                statementPrototype: $container->get(Mysqli\Statement::class),
                resultPrototype: $container->get(Mysqli\Result::class),
                options: $adapterConfig['options'] ?? [],
            ),
            default => throw new RuntimeException('Unable to create PdoDriver from configuration.'),
        };
    }
}
