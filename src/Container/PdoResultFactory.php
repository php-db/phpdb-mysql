<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Psr\Container\ContainerInterface;

final class PdoResultFactory
{
    public function __invoke(ContainerInterface $container): ResultInterface&Result
    {
        return new Result();
    }
}
