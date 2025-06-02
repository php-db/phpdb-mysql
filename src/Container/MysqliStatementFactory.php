<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Statement;
use Psr\Container\ContainerInterface;

final class MysqliStatementFactory
{
    public function __invoke(ContainerInterface $container): StatementInterface
    {
        $bufferResults = $container->get('config')['db']['options']['buffer_results'] ?? false;
        return new Statement($bufferResults);
    }
}
