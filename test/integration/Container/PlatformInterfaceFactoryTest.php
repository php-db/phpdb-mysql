<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Mysql\AdapterPlatform as Mysql;
use PhpDb\Mysql\Container\PlatformInterfaceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('container')]
#[CoversClass(PlatformInterfaceFactory::class)]
#[CoversMethod(PlatformInterfaceFactory::class, '__invoke')]
final class PlatformInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsPlatformInterfaceWhenDbDriverIsPdo(): void
    {
        $factory  = new PlatformInterfaceFactory();
        $instance = $factory($this->container, Mysql::class, ['driver' => $this->adapter->getDriver()]);
        self::assertInstanceOf(PlatformInterface::class, $instance);
        self::assertInstanceOf(Mysql::class, $instance);
    }
}
