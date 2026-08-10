<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Container\DriverInterfaceFactory;
use PhpDb\Mysql\Driver;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(DriverInterfaceFactory::class)]
#[Attributes\CoversMethod(DriverInterfaceFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class DriverInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testFactoryReturnsMysqliDriver(): void
    {
        $factory = new DriverInterfaceFactory();
        $driver  = $factory(
            $this->container,
            DriverInterface::class,
            $this->config[AdapterInterface::class],
        );
        self::assertInstanceOf(DriverInterface::class, $driver);
        $this->assertInstanceOf(Driver::class, $driver);
    }

    public function testInvokeThrowsExceptionWithoutConnectionConfig(): void
    {
        $this->expectException(ContainerException::class);

        $factory = new DriverInterfaceFactory();
        $factory(
            $this->container,
            Connection::class,
        );
    }
}
