<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Exception\RuntimeException;
use PhpDb\Mysql\Pdo\Driver;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Driver::class, 'createResult')]
final class DriverTest extends TestCase
{
    protected Driver $pdo;

    /** @psalm-return array<array-key, array{0: string}> */
    public static function getInvalidParamName(): array
    {
        return [
            ['foo%'],
            ['foo-'],
            ['foo$'],
            ['foo0!'],
        ];
    }

    /** @psalm-return array<array-key, array{0: int|string, 1: null|string, 2: string}> */
    public static function getParamsAndType(): array
    {
        return [
            ['foo',     null,                                    ':foo'],
            ['foo_bar', null,                                    ':foo_bar'],
            ['123foo',  null,                                    ':123foo'],
            [1,         null,                                    '?'],
            ['1',       null,                                    '?'],
            ['foo',     DriverInterface::PARAMETERIZATION_NAMED, ':foo'],
            ['foo_bar', DriverInterface::PARAMETERIZATION_NAMED, ':foo_bar'],
            ['123foo',  DriverInterface::PARAMETERIZATION_NAMED, ':123foo'],
            [1,         DriverInterface::PARAMETERIZATION_NAMED, ':1'],
            ['1',       DriverInterface::PARAMETERIZATION_NAMED, ':1'],
            [':foo',    null,                                    ':foo'],
        ];
    }

    #[Test]
    public function createResultPassesNullRowCount(): void
    {
        $pdoStatement = $this->getMockBuilder(PDOStatement::class)->getMock();
        $pdoStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(4);

        $connection = $this->createStub(AbstractPdoConnection::class);
        $statement  = $this->createStub(Statement::class);
        $driver     = new Driver($connection, $statement, new Result());

        $result = $driver->createResult($pdoStatement);

        static::assertInstanceOf(Result::class, $result);
        static::assertSame(4, $result->count());
    }

    #[Test]
    #[DataProvider('getParamsAndType')]
    public function formatParameterName(int|string $name, ?string $type, string $expected): void
    {
        $result = $this->pdo->formatParameterName($name, $type);
        static::assertEquals($expected, $result);
    }

    #[Test]
    #[DataProvider('getInvalidParamName')]
    public function formatParameterNameWithInvalidCharacters(string $name): void
    {
        $this->expectException(RuntimeException::class);
        $this->pdo->formatParameterName($name);
    }

    #[Test]
    public function getResultPrototype(): void
    {
        $resultPrototype = $this->pdo->getResultPrototype();

        static::assertInstanceOf(Result::class, $resultPrototype);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $connection = $this->createStub(AbstractPdoConnection::class);
        $statement  = $this->createStub(Statement::class);
        $result     = $this->createStub(Result::class);
        $this->pdo  = new Driver(
            $connection,
            $statement,
            $result,
        );
    }
}
