<?php declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql;

use Laminas\Db\Adapter\AdapterAbstractServiceFactory;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\AdapterServiceFactory;
use Laminas\Db\Container\AdapterManager;
use Laminas\Db\Container\MetadataFactory;
use Laminas\Db\Metadata\MetadataInterface;

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
            // 'abstract_factories' => [
            //     AdapterAbstractServiceFactory::class,
            // ],
            'aliases'            => [
                AdapterInterface::class  => Adapter::class,
                //MetadataInterface::class => Metadata\Source\MysqlMetadata::class,
            ],
            'delegators'         => [
                AdapterManager::class => [
                    Container\AdapterManagerDelegator::class,
                ],
            ],
            'factories'          => [
                Adapter::class => Container\AdapterFactory::class,
                //Metadata\Source\MysqlMetadata::class => MetadataFactory::class,
            ],
        ];
    }
}