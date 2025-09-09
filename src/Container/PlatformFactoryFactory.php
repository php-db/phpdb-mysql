<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\Mysql\Container\PlatformInterfaceFactory;

final class PlatformFactoryFactory
{
    public function __invoke(): PlatformInterfaceFactory
    {
        return new PlatformInterfaceFactory();
    }
}
