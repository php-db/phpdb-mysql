<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Container\PlatformInterfaceFactory;
use PhpDb\Mysql\Pdo\Driver as PdoDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('container')]
#[CoversClass(PlatformInterfaceFactory::class)]
#[CoversMethod(PlatformInterfaceFactory::class, '__invoke')]
final class PlatformInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    #[Test]
    public function invokeReturnsPlatformInterfaceWhenDbDriverIsPdo(): void
    {
        $adapter = $this->getAdapter(['driver' => PdoDriver::class]);

        $this->config[AdapterInterface::class]['driver'] = $adapter->getDriver();

        $factory  = new PlatformInterfaceFactory();
        $instance = $factory(
            $this->container,
            PlatformInterface::class,
            $this->config[AdapterInterface::class],
        );

        static::assertInstanceOf(PlatformInterface::class, $instance);
        static::assertInstanceOf(AdapterPlatform::class, $instance);
    }

    #[Test]
    public function invokeThrowsForInvalidDriver(): void
    {
        $factory = new PlatformInterfaceFactory();

        $this->expectException(ContainerException::class);
        $factory($this->container, PlatformInterface::class, ['driver' => 'not-a-driver']);
    }
}
