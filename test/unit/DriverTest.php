<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Result;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('mysqli')]
#[CoversMethod(Driver::class, '__construct')]
#[CoversMethod(Driver::class, 'checkEnvironment')]
#[CoversMethod(Driver::class, 'formatParameterName')]
#[CoversMethod(Driver::class, 'getConnection')]
#[CoversMethod(Driver::class, 'getLastGeneratedValue')]
#[CoversMethod(Driver::class, 'getPrepareType')]
#[CoversMethod(Driver::class, 'getProfiler')]
#[CoversMethod(Driver::class, 'getResultPrototype')]
#[CoversMethod(Driver::class, 'getStatementPrototype')]
#[CoversMethod(Driver::class, 'setProfiler')]
final class DriverTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getLastGeneratedValue')->willReturn(42);

        $driver = new Driver($connection);

        static::assertTrue($driver->checkEnvironment());
        static::assertSame($connection, $driver->getConnection());
        static::assertSame('?', $driver->formatParameterName('name'));
        static::assertSame(DriverInterface::PARAMETERIZATION_POSITIONAL, $driver->getPrepareType());
        static::assertSame(42, $driver->getLastGeneratedValue());
        static::assertNull($driver->getProfiler());
        static::assertInstanceOf(Statement::class, $driver->getStatementPrototype());
        static::assertInstanceOf(Result::class, $driver->getResultPrototype());
    }

    #[Test]
    public function setProfiler(): void
    {
        $driver   = new Driver($this->createStub(Connection::class));
        $profiler = $this->createStub(ProfilerInterface::class);

        static::assertSame($driver, $driver->setProfiler($profiler));
        static::assertSame($profiler, $driver->getProfiler());
    }
}
