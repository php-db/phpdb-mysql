<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\PdoDriverInterface;
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
}
