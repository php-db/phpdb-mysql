<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Mysql\Result;
use Psr\Container\ContainerInterface;

final class MysqliResultFactory
{
    public function __invoke(ContainerInterface $container): ResultInterface&Result
    {
        return new Result();
    }
}
