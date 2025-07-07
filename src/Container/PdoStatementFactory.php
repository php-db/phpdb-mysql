<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\StatementInterface;
use Psr\Container\ContainerInterface;

final class PdoStatementFactory
{
    public function __invoke(ContainerInterface $container): StatementInterface&Statement
    {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $options */
        $options = $dbConfig['options'] ?? [];

        return new Statement(options: $options);
    }
}
