<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Container\PlatformInterfaceFactory;
use PhpDb\Mysql\Pdo\Driver as PdoDriver;
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
        $adapter = $this->getAdapter(['driver' => PdoDriver::class]);

        $this->config[AdapterInterface::class]['driver'] = $adapter->getDriver();

        $factory  = new PlatformInterfaceFactory();
        $instance = $factory(
            $this->container,
            PlatformInterface::class,
            $this->config[AdapterInterface::class],
        );

        self::assertInstanceOf(PlatformInterface::class, $instance);
        self::assertInstanceOf(AdapterPlatform::class, $instance);
    }
}
