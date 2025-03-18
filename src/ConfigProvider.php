<?php declare(strict_types=1);

namespace Laminas\Db\Mysql;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;

readonly class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                DriverInterface::class   => Driver\Mysqli\Driver::class,
                PlatformInterface::class => Platform\Mysql::class,
            ],
            'factories' => [
                Adapter::class        => AdapterServiceFactory::class,
                Driver\Mysqli\Driver::class => Driver\
                Platform\Mysql::class => InvokableFactory::class,
            ],
        ];
    }
}