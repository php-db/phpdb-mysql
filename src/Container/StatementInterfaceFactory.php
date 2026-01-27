<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Mysql\Statement;
use Psr\Container\ContainerInterface;

final class StatementInterfaceFactory
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
