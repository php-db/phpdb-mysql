<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Mysql\Container\MysqliDriverFactory;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Mysqli;
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
