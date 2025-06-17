<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Psr\Container\ContainerInterface;

final class PdoStatementFactory
{
    public function __invoke(ContainerInterface $container): Statement
    {
        $options = $container->get('config')['db']['options'] ?? false;
        return new Statement($options);
    }
}
