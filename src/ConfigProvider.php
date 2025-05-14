<?php declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\AdapterAbstractServiceFactory;
use Laminas\Db\Adapter\AdapterServiceFactory;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Container\MetadataFactory;
use Laminas\Db\Metadata\MetadataInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;
use PHPUnit\Metadata\Metadata;

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
            'aliases'            => [
                AdapterInterface::class  => Adapter::class,
                MetadataInterface::class => Metadata\Source\MysqlMetadata::class,
            ],
            'factories'          => [
                Adapter::class                       => AdapterServiceFactory::class,
                Metadata\Source\MysqlMetadata::class => MetadataFactory::class,
            ],
        ];
    }
}