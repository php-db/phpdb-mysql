<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql;

use mysqli_stmt;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Statement::class, 'getProfiler')]
#[CoversMethod(Statement::class, 'setProfiler')]
#[CoversMethod(Statement::class, 'setDriver')]
#[CoversMethod(Statement::class, '__clone')]
#[Group('unit')]
final class StatementTest extends TestCase
{
    #[Test]
    public function cloneCopiesTheParameterContainer(): void
    {
        $container = new ParameterContainer(['id' => 1]);
        $statement = new Statement($container);

        $clone = clone $statement;

        static::assertNotSame($container, $clone->getParameterContainer());
        static::assertEquals($container, $clone->getParameterContainer());
    }

    #[Test]
    public function cloneIsNotPrepared(): void
    {
        $statement = new Statement();
        $statement->setResource($this->createStub(mysqli_stmt::class));

        $clone = clone $statement;

        static::assertFalse($clone->isPrepared());
    }

    #[Test]
    public function profilerAccessors(): void
    {
        $statement = new Statement();

        static::assertNull($statement->getProfiler());

        $profiler = $this->createStub(ProfilerInterface::class);

        static::assertSame($statement, $statement->setProfiler($profiler));
        static::assertSame($profiler, $statement->getProfiler());
    }

    #[Test]
    public function setDriverRejectsNonMysqlDriver(): void
    {
        $statement = new Statement();

        $this->expectException(InvalidArgumentException::class);
        $statement->setDriver($this->createStub(DriverInterface::class));
    }
}
