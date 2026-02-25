<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Mysql\Container\PdoConnectionInterfaceFactory;
use PhpDb\Mysql\Pdo\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('container')]
#[Group('integration')]
#[CoversClass(PdoConnectionInterfaceFactory::class)]
#[CoversMethod(PdoConnectionInterfaceFactory::class, '__invoke')]
final class PdoConnectionFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsPdoConnection(): void
    {
        $factory  = new PdoConnectionInterfaceFactory();
        $instance = $factory($this->container);
        self::assertInstanceOf(ConnectionInterface::class, $instance);
        self::assertInstanceOf(PdoConnectionInterface::class, $instance);
        self::assertInstanceOf(Connection::class, $instance);
    }
}
