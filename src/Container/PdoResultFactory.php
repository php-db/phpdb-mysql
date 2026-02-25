<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\ResultInterface;
use Psr\Container\ContainerInterface;

final class PdoResultFactory
{
    public function __invoke(ContainerInterface $container): ResultInterface&Result
    {
        return new Result();
    }
}
