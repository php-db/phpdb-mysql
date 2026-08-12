<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Container\DriverInterfaceFactory;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Result;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(DriverInterfaceFactory::class)]
#[Attributes\CoversMethod(DriverInterfaceFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class DriverInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    #[Test]
    public function factoryReturnsMysqliDriver(): void
    {
        $factory = new DriverInterfaceFactory();
        $driver  = $factory(
            $this->container,
            DriverInterface::class,
            $this->config[AdapterInterface::class],
        );
        static::assertInstanceOf(DriverInterface::class, $driver);
        static::assertInstanceOf(Driver::class, $driver);
    }

    #[Test]
    public function invokeThrowsExceptionWithoutConnectionConfig(): void
    {
        $this->expectException(ContainerException::class);

        $factory = new DriverInterfaceFactory();
        $factory(
            $this->container,
            Connection::class,
        );
    }

    #[Test]
    public function invokeUsesRegisteredResultInterface(): void
    {
        /** @var ServiceManager $container */
        $container = $this->container;
        $container->setService(ResultInterface::class, new Result());

        $factory = new DriverInterfaceFactory();
        $driver  = $factory(
            $container,
            DriverInterface::class,
            $this->config[AdapterInterface::class],
        );

        static::assertInstanceOf(Driver::class, $driver);
    }
}
