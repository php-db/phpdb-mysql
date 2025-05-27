<?php declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql;

use Laminas\Db\Container\AdapterManager;

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