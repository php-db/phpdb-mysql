<?php

declare(strict_types=1);

namespace Laminas\Db\Mysql\Platform;

use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class PlatformFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): PlatformInterface
    {
        return new Mysql();
    }
}
