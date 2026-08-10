<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
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
final class PdoConnectionInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsPdoConnection(): void
    {
        $factory  = new PdoConnectionInterfaceFactory();
        $instance = $factory(
            $this->container,
            PdoConnectionInterface::class,
            $this->config[AdapterInterface::class],
        );
        self::assertInstanceOf(ConnectionInterface::class, $instance);
        self::assertInstanceOf(PdoConnectionInterface::class, $instance);
        self::assertInstanceOf(Connection::class, $instance);
    }

    public function testInvokeThrowsExceptionWithoutConnectionConfig(): void
    {
        $this->expectException(InvalidConnectionParametersException::class);

        $factory = new PdoConnectionInterfaceFactory();
        $factory(
            $this->container,
            PdoConnectionInterface::class,
        );
    }
}
