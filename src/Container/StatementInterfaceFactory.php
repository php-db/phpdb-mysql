<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\Pdo\Statement as PdoStatement;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Mysql\Driver\Mysqli\Statement as MysqliStatement;
use Psr\Container\ContainerInterface;

/** @internal */
final class StatementInterfaceFactory
{
    /**
     * @param string $requestedName
     * @phpstan-param class-string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName
    ): (StatementInterface&PdoStatement)|(StatementInterface&MysqliStatement) {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $options */
        $options = $dbConfig['options'] ?? [];

        return new $requestedName(options: $options);
    }
}
