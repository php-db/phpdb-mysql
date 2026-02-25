<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Container\ConnectionInterfaceFactory;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(ConnectionInterfaceFactory::class)]
#[Attributes\CoversMethod(ConnectionInterfaceFactory::class, '__invoke')]
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

        $factory    = new ConnectionInterfaceFactory();
        $connection = $factory($this->container, Connection::class, $this->config['db']);

        self::assertInstanceOf(ConnectionInterface::class, $connection);
        self::assertInstanceOf(Connection::class, $connection);
    }
}
