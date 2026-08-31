<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\Mysql\Sql\SelectDecorator;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(SelectDecorator::class, 'setSubject')]
#[CoversMethod(SelectDecorator::class, 'localizeVariables')]
#[CoversMethod(SelectDecorator::class, 'processLimit')]
#[CoversMethod(SelectDecorator::class, 'processOffset')]
final class SelectDecoratorTest extends TestCase
{
    protected AdapterPlatform $platform;

    #[Test]
    public function limitAndOffset(): void
    {
        $select = (new Select('test'))->limit(10)
            ->offset(5);
        $sql = $this->decorate($select)->getSqlString($this->platform);

        static::assertStringContainsString('LIMIT 10', $sql);
        static::assertStringContainsString('OFFSET 5', $sql);
    }

    #[Test]
    public function limitOnly(): void
    {
        $select = (new Select('test'))->limit(10);
        $sql    = $this->decorate($select)->getSqlString($this->platform);

        static::assertStringContainsString('LIMIT 10', $sql);
        static::assertStringNotContainsString('OFFSET', $sql);
    }

    #[Test]
    public function noLimitOrOffset(): void
    {
        $select = new Select('test');
        $sql    = $this->decorate($select)->getSqlString($this->platform);

        static::assertStringNotContainsString('LIMIT', $sql);
        static::assertStringNotContainsString('OFFSET', $sql);
    }

    #[Test]
    public function offsetWithoutLimit(): void
    {
        $select = (new Select('test'))->offset(5);
        $sql    = $this->decorate($select)->getSqlString($this->platform);

        static::assertStringContainsString('LIMIT 18446744073709551615', $sql);
        static::assertStringContainsString('OFFSET 5', $sql);
    }

    #[Test]
    public function prepareStatementBindsLimitAndOffsetParameters(): void
    {
        $decorator = $this->decorate(
            (new Select('test'))->limit(10)
                ->offset(5),
        );

        $parameterContainer = new ParameterContainer();

        $statementContainer = $this->createMock(StatementContainerInterface::class);
        $statementContainer->method('getParameterContainer')->willReturn($parameterContainer);
        $statementContainer->expects($this->once())
            ->method('setSql')
            ->with($this->isString());

        $driver = $this->createStub(DriverInterface::class);
        $driver->method('formatParameterName')
            ->willReturnCallback(static fn(string $name): string => ":{$name}");

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getPlatform')->willReturn($this->platform);
        $adapter->method('getDriver')->willReturn($driver);

        $result = $decorator->prepareStatement($adapter, $statementContainer);

        static::assertSame($statementContainer, $result);
        static::assertSame(10, $parameterContainer->offsetGet('limit'));
        static::assertSame(5, $parameterContainer->offsetGet('offset'));
    }

    #[Test]
    public function setSubjectReturnsSelf(): void
    {
        $decorator = new SelectDecorator();

        static::assertSame($decorator, $decorator->setSubject(new Select('test')));
        static::assertSame($decorator, $decorator->setSubject(null));
    }

    #[Override]
    protected function setUp(): void
    {
        $driver = new Driver(
            $this->createStub(AbstractPdoConnection::class),
            $this->createStub(Statement::class),
            $this->createStub(Result::class),
        );
        $this->platform = new AdapterPlatform($driver);
    }

    private function decorate(Select $select): SelectDecorator
    {
        $decorator = new SelectDecorator();
        $decorator->setSubject($select);

        return $decorator;
    }
}
