<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql;

use PhpDb\Container\AdapterManager;

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
            'delegators' => [
                AdapterManager::class => [
                    Container\AdapterManagerDelegator::class,
                ],
            ],
        ];
    }
}
