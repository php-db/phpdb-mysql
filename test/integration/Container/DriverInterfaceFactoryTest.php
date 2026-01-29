<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Driver\DriverInterface;
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
        $this->getAdapter([
            'db' => [
                'driver' => 'Mysqli',
            ],
        ]);
        $factory = new DriverInterfaceFactory();
        $driver  = $factory($this->container, Driver::class, ['connection' => ['foo' => 'bar']]);
        self::assertInstanceOf(DriverInterface::class, $driver);
        $this->assertInstanceOf(Driver::class, $driver);
    }
}
