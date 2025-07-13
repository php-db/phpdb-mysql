<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql;

use PhpDb\Adapter\Mysql\Metadata\Source\MysqlMetadata;
use PhpDb\Container\AdapterManager;
use PhpDb\Container\MetadataFactory;
use PhpDb\Metadata\MetadataInterface;

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
            'aliases'    => [
                MetadataInterface::class => MysqlMetadata::class,
            ],
            'factories'  => [
                MysqlMetadata::class => MetadataFactory::class,
            ],
            'delegators' => [
                AdapterManager::class => [
                    Container\AdapterManagerDelegator::class,
                ],
            ],
        ];
    }
}
