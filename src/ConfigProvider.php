<?php declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql;

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
            'abstract_factories' => [
                AdapterAbstractServiceFactory::class,
            ],
            'factories'          => [
                AdapterInterface::class => AdapterServiceFactory::class,
            ],
            'aliases'            => [
                Adapter::class => AdapterInterface::class,
            ],
        ];
    }
}