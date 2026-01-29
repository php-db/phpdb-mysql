<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Container\PlatformInterfaceFactory;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver;
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
        $driver   = new Driver(
            new Connection(['foo' => 'bar']),
            new Statement(),
            new Result(),
        );
        $factory  = new PlatformInterfaceFactory();
        $instance = $factory($this->container, 'foo', ['driver' => $driver]);
        self::assertInstanceOf(PlatformInterface::class, $instance);
        self::assertInstanceOf(AdapterPlatform::class, $instance);
    }
}
