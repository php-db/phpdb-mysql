<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Mysql\Container\AdapterFactory;
use LaminasIntegrationTest\Db\Adapter\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdapterFactory::class)]
#[CoversMethod(AdapterFactory::class, '__invoke')]
final class AdapterFactoryTest extends TestCase
{
    use SetupTrait;

    public function testFactoryReturnsAdapterInterface(): void
    {
        $factory = new AdapterFactory();
        $adapter = $factory($this->container);
        self::assertInstanceOf(AdapterInterface::class, $adapter);
    }
}
