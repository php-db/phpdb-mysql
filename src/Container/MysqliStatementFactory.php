<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Statement;
use Psr\Container\ContainerInterface;

final class MysqliStatementFactory
{
    public function __invoke(ContainerInterface $container): StatementInterface&Statement
    {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $dbConfig */
        $dbConfig = $config['db'] ?? [];

        /** @var array $options */
        $options = $dbConfig['options'] ?? [];

        /** @var bool $bufferResults */
        $bufferResults = $options['buffer_results'] ?? false;

        return new Statement(bufferResults: $bufferResults);
    }
}
