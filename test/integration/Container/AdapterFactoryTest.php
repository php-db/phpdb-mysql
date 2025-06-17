<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Mysql\Container\AdapterFactory;
use Laminas\Db\Adapter\Mysql\Container\AdapterManagerDelegator;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Container\AdapterManager;
use Laminas\Db\Container\AdapterManagerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class AdapterFactoryTest extends TestCase
{

    public function testFactoryReturnsAdapterInterface(): void
    {
        $this->markTestIncomplete(
            'This test is incomplete and needs to be implemented.'
        );
    }
}
