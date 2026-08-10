<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Mysql\Statement;
use Psr\Container\ContainerInterface;

final class StatementInterfaceFactory
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): StatementInterface&Statement {
        return new Statement(bufferResults: $options['buffer_results'] ?? false);
    }
}
