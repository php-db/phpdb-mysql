<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Mysql\Container\PdoDriverInterfaceFactory;
use PhpDb\Mysql\Pdo\Driver as PdoDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('container')]
#[Group('integration')]
#[CoversClass(PdoDriverInterfaceFactory::class)]
#[CoversMethod(PdoDriverInterfaceFactory::class, '__invoke')]
final class PdoDriverFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsPdoDriver(): void
    {
        $factory  = new PdoDriverInterfaceFactory();
        $instance = $factory($this->container);

        self::assertInstanceOf(PdoDriverInterface::class, $instance);
        self::assertInstanceOf(PdoDriver::class, $instance);
    }
}
