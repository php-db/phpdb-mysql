<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Mysql\Driver\Mysqli\Result;
use Psr\Container\ContainerInterface;

final class MysqliResultFactory
{
    public function __invoke(ContainerInterface $container): ResultInterface&Result
    {
        return new Result();
    }
}
