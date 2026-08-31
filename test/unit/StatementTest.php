<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Statement::class, 'getProfiler')]
#[CoversMethod(Statement::class, 'setProfiler')]
#[CoversMethod(Statement::class, 'setDriver')]
final class StatementTest extends TestCase
{
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
