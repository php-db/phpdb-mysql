<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Mysql\Driver\Pdo\Connection;
use PhpDb\Adapter\Mysql\Driver\Pdo\Pdo as PdoDriver;
use Psr\Container\ContainerInterface;
use RuntimeException;

final class PdoDriverFactory
{
    public function __invoke(ContainerInterface $container): PdoDriverInterface&PdoDriver
    {
        /** @var ConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $container->get(Connection::class);

        $hasStatement = $container->has(Statement::class);
        $hasResult    = $container->has(Result::class);

        return match (true) {
            ! $hasStatement && ! $hasResult => new PdoDriver(
                connection: $connectionInstance,
            ),
            $hasStatement && ! $hasResult => new PdoDriver(
                connection: $connectionInstance,
                statementPrototype: $container->get(Statement::class),
            ),
            ! $hasStatement && $hasResult => new PdoDriver(
                connection: $connectionInstance,
                resultPrototype: $container->get(Result::class),
            ),
            $hasStatement && $hasResult => new PdoDriver(
                connection: $connectionInstance,
                statementPrototype: $container->get(Statement::class),
                resultPrototype: $container->get(Result::class),
            ),
            default => throw new RuntimeException('Unable to create PdoDriver'),
        };
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

        /** @var ConnectionInterface&Connection $connectionInstance */
        $connectionInstance = $connectionFactory::createFromConfig($container, $requestedName);

        $hasStatement = $container->has(Statement::class);
        $hasResult    = $container->has(Result::class);

        return match (true) {
            ! $hasStatement && ! $hasResult => new PdoDriver(
                connection: $connectionInstance,
            ),
            $hasStatement && ! $hasResult => new PdoDriver(
                connection: $connectionInstance,
                statementPrototype: $container->get(Statement::class),
            ),
            ! $hasStatement && $hasResult => new PdoDriver(
                connection: $connectionInstance,
                resultPrototype: $container->get(Result::class),
            ),
            $hasStatement && $hasResult => new PdoDriver(
                connection: $connectionInstance,
                statementPrototype: $container->get(Statement::class),
                resultPrototype: $container->get(Result::class),
            ),
            default => throw new RuntimeException('Unable to create PdoDriver from configuration.'),
        };
    }
}
