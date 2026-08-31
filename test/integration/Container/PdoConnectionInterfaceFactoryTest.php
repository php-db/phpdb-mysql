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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('container')]
#[Group('integration')]
#[CoversClass(PdoConnectionInterfaceFactory::class)]
#[CoversMethod(PdoConnectionInterfaceFactory::class, '__invoke')]
final class PdoConnectionInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    #[Test]
    public function invokeReturnsPdoConnection(): void
    {
        $factory  = new PdoConnectionInterfaceFactory();
        $instance = $factory(
            $this->container,
            PdoConnectionInterface::class,
            $this->config[AdapterInterface::class],
        );
        static::assertInstanceOf(ConnectionInterface::class, $instance);
        static::assertInstanceOf(PdoConnectionInterface::class, $instance);
        static::assertInstanceOf(Connection::class, $instance);
    }

    #[Test]
    public function invokeThrowsExceptionWithoutConnectionConfig(): void
    {
        $this->expectException(InvalidConnectionParametersException::class);

        $factory = new PdoConnectionInterfaceFactory();
        $factory(
            $this->container,
            PdoConnectionInterface::class,
        );
    }
}
