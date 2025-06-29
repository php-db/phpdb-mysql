<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Mysql\Container\MysqliConnectionFactory;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Connection;
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
        $connection = $factory($this->container);

        self::assertInstanceOf(ConnectionInterface::class, $connection);
        self::assertInstanceOf(Connection::class, $connection);
    }
}
