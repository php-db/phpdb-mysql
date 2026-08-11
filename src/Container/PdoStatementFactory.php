<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\StatementInterface;
use Psr\Container\ContainerInterface;

final class PdoStatementFactory
{
    /**
     * @param array<string, mixed>|null $options
     */
    // @mago-expect analysis:unused-parameter
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): StatementInterface&Statement {
        return new Statement(options: $options);
    }
}
