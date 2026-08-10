<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use PDO;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

use function assert;
use function uniqid;

#[CoversMethod(Result::class, 'current')]
#[CoversMethod(Result::class, 'count')]
#[Group('result-pdo')]
final class ResultTest extends TestCase
{
    public function testCountWithClosureRowCountInvokesClosure(): void
    {
        $mock = $this->getMockBuilder(PDOStatement::class)->getMock();
        $mock->expects($this->never())
            ->method('rowCount');

        $result = new Result();
        $result->initialize($mock, null, fn() => 3);

        self::assertSame(3, $result->count());
    }

    public function testCountWithIntRowCountReturnsValueWithoutQueryingPdo(): void
    {
        $mock = $this->getMockBuilder(PDOStatement::class)->getMock();
        $mock->expects($this->never())
            ->method('rowCount');

        $result = new Result();
        $result->initialize($mock, null, 7);

        self::assertSame(7, $result->count());
    }

    public function testCountWithNullRowCountDelegatesToPdoStatement(): void
    {
        $mock = $this->getMockBuilder(PDOStatement::class)->getMock();
        $mock->expects($this->once())
            ->method('rowCount')
            ->willReturn(4);

        $result = new Result();
        $result->initialize($mock, null, null);

        self::assertSame(4, $result->count());
    }

    public function testCountWithZeroRowCountReturnsZeroWithoutQueryingPdo(): void
    {
        $mock = $this->getMockBuilder(PDOStatement::class)->getMock();
        $mock->expects($this->never())
            ->method('rowCount');

        $result = new Result();
        $result->initialize($mock, null, 0);

        self::assertSame(0, $result->count());
    }

    /**
     * Tests current method returns same data on consecutive calls.
     */
    public function testCurrent(): void
    {
        $mock = $this->getMockBuilder(PDOStatement::class)->getMock();
        $mock->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn() => uniqid());

        $result = new Result();
        $result->initialize($mock, null);

        self::assertEquals($result->current(), $result->current());
    }

    /**
     * Tests whether the fetch mode was set properly and
     */
    public function testFetchModeAnonymousObject(): void
    {
        $mock = $this->getMockBuilder(PDOStatement::class)->getMock();
        $mock->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn() => new stdClass());

        $result = new Result();
        $result->initialize($mock, null);
        $result->setFetchMode(PDO::FETCH_OBJ);

        self::assertEquals(5, $result->getFetchMode());
        self::assertInstanceOf('stdClass', $result->current());
    }

    public function testFetchModeException(): void
    {
        $result = new Result();

        $this->expectException(InvalidArgumentException::class);
        $result->setFetchMode(13);
    }

    /**
     * Tests whether the fetch mode has a broader range
     */
    public function testFetchModeRange(): void
    {
        $mock = $this->getMockBuilder(PDOStatement::class)->getMock();
        $mock->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn() => new stdClass());
        $result = new Result();
        $result->initialize($mock, null);
        $result->setFetchMode(PDO::FETCH_NAMED);
        self::assertEquals(11, $result->getFetchMode());
        self::assertInstanceOf('stdClass', $result->current());
    }

    public function testMultipleRewind(): void
    {
        $data = [
            ['test' => 1],
            ['test' => 2],
        ];
        $position = 0;

        $mock = $this->getMockBuilder(PDOStatement::class)->getMock();
        assert($mock instanceof PDOStatement); // to suppress IDE type warnings
        $mock->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(function () use ($data, &$position) {
                return $data[$position++];
            });
        $result = new Result();
        $result->initialize($mock, null);

        $result->rewind();
        $result->rewind();

        $this->assertEquals(0, $result->key());
        $this->assertEquals(1, $position);
        $this->assertEquals($data[0], $result->current());

        $result->next();
        $this->assertEquals(1, $result->key());
        $this->assertEquals(2, $position);
        $this->assertEquals($data[1], $result->current());
    }
}
