<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Container\ConnectionInterfaceFactory;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(ConnectionInterfaceFactory::class)]
#[Attributes\CoversMethod(ConnectionInterfaceFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class ConnectionInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    #[Test]
    public function invokeReturnsMysqliConnection(): void
    {
        $factory    = new ConnectionInterfaceFactory();
        $connection = $factory(
            $this->container,
            Connection::class,
            $this->config[AdapterInterface::class],
        );

        static::assertInstanceOf(ConnectionInterface::class, $connection);
        static::assertInstanceOf(Connection::class, $connection);
    }

    #[Test]
    public function invokeThrowsExceptionWithoutConnectionConfig(): void
    {
        $this->expectException(InvalidConnectionParametersException::class);

        $factory = new ConnectionInterfaceFactory();
        $factory(
            $this->container,
            Connection::class,
        );
    }
}
