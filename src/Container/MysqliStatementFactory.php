<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Mysql\Driver\Mysqli\Statement;
use Psr\Container\ContainerInterface;

/** @internal */
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
