<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Mysql\Container\PdoDriverInterfaceFactory;
use PhpDb\Mysql\Pdo\Driver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('container')]
#[Group('integration')]
#[CoversClass(PdoDriverInterfaceFactory::class)]
#[CoversMethod(PdoDriverInterfaceFactory::class, '__invoke')]
final class PdoDriverInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    #[Test]
    public function invokeReturnsPdoDriver(): void
    {
        $factory  = new PdoDriverInterfaceFactory();
        $instance = $factory(
            $this->container,
            PdoDriverInterface::class,
            $this->config[AdapterInterface::class],
        );

        static::assertInstanceOf(PdoDriverInterface::class, $instance);
        static::assertInstanceOf(Driver::class, $instance);
    }

    #[Test]
    public function invokeThrowsWhenOptionsMissingConnection(): void
    {
        $factory = new PdoDriverInterfaceFactory();

        $this->expectException(ContainerException::class);
        $factory($this->container, PdoDriverInterface::class, options: null);
    }

    #[Test]
    public function invokeUsesRegisteredResultInterface(): void
    {
        /** @var ServiceManager $container */
        $container = $this->container;
        $container->setService(ResultInterface::class, new Result());

        $factory  = new PdoDriverInterfaceFactory();
        $instance = $factory(
            $container,
            PdoDriverInterface::class,
            $this->config[AdapterInterface::class],
        );

        static::assertInstanceOf(Driver::class, $instance);
    }
}
