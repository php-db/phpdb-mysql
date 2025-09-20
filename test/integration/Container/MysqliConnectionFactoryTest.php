<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Mysql\Container\MysqliConnectionFactory;
use PhpDb\Adapter\Mysql\Driver\Mysqli\Connection;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(MysqliConnectionFactory::class)]
#[Attributes\CoversMethod(MysqliConnectionFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class MysqliConnectionFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsMysqliConnection(): void
    {
        $this->getAdapter([
            'db' => [
                'driver' => 'Mysqli',
            ],
        ]);

        $factory    = new MysqliConnectionFactory();
        $connection = $factory($this->container, Connection::class);

        self::assertInstanceOf(ConnectionInterface::class, $connection);
        self::assertInstanceOf(Connection::class, $connection);
    }
}
