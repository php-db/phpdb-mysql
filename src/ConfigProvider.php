<?php declare(strict_types=1);

namespace Laminas\Db\Mysql;

use Laminas\Db\Adapter\AbstractAdapterServiceFactory;
use Laminas\Db\Adapter\AdapterInterface;
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
            'aliases' => [
                PlatformInterface::class => Platform\Mysql::class,
            ],
            'factories' => [
                AdapterInterface::class => AdapterServiceFactory::class,
                //DriverInterface::class => Driver\Mysqli\DriverFactory::class,
                DriverInterface::class   => Driver\Pdo\DriverFactory::class,
                Platform\Mysql::class    => InvokableFactory::class,
            ],
        ];
    }
}