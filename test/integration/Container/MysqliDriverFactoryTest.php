<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Mysql\Container\MysqliDriverFactory;
use PhpDb\Adapter\Mysql\Driver\Mysqli\Mysqli;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(MysqliDriverFactory::class)]
#[Attributes\CoversMethod(MysqliDriverFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class MysqliDriverFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testFactoryReturnsMysqliDriver(): void
    {
        $this->getAdapter([
            'db' => [
                'driver' => 'Mysqli',
            ],
        ]);
        $factory = new MysqliDriverFactory();
        $driver  = $factory($this->container);
        self::assertInstanceOf(DriverInterface::class, $driver);
        $this->assertInstanceOf(Mysqli::class, $driver);
    }
}
