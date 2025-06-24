<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Laminas\Db\Adapter\Driver\StatementInterface;
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
