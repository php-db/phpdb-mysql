<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Mysql\Container\PlatformInterfaceFactory;
use PhpDb\Container\PlatformInterfaceFactoryFactoryInterface as FactoryFactoryInterface;

final class PlatformInterfaceFactoryFactory implements FactoryFactoryInterface
{
    public function __invoke(): callable
    {
        return new PlatformInterfaceFactory();
    }
}
