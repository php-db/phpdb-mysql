<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter\Mysql\Sql\Platform;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Mysql\Platform\Mysql as AdapterPlatform;
use PhpDb\Adapter\StatementContainer;
use PhpDb\Adapter\Mysql\Sql\Platform\Mysql\Ddl\AlterTableDecorator;
use PhpDb\Adapter\Mysql\Sql\Platform\Mysql\Ddl\CreateTableDecorator;
use PhpDb\Adapter\Mysql\Sql\Platform\Mysql\Mysql as SqlPlatform;
use PhpDb\Adapter\Mysql\Sql\Platform\Mysql\SelectDecorator;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Select;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDbTest\Adapter\Mysql\TestAsset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SqlPlatform::class)]
#[CoversMethod(SqlPlatform::class, 'getDecorators')]
#[CoversMethod(SqlPlatform::class, 'getTypeDecorator')]
final class PlatformTest extends TestCase
{
    public function testGetDecoratorsReturnsArrayOfDecorators(): void
    {
        $adapter  = $this->getAdapter();
        $sqlPlatform = new SqlPlatform();
        $decorators = $sqlPlatform->getDecorators();
        self::assertIsArray($decorators);
    }

    public function testGetTypeDecoratorReturnsDecoratorForSelect(): void
    {
        $adapter  = $this->getAdapter();
        $sqlPlatform = new SqlPlatform();
        $select = new Select();
        $decorator = $sqlPlatform->getTypeDecorator($select);
        self::assertInstanceOf(SelectDecorator::class, $decorator);
    }

    protected function getAdapter(): Adapter
    {
        /** @var DriverInterface|MockObject $mockDriver */
        $mockDriver = $this->getMockBuilder(DriverInterface::class)->getMock();

        $adapterPlatform = new AdapterPlatform($mockDriver);

        $mockDriver->expects($this->any())
            ->method('formatParameterName')
            ->willReturn('?');
        $mockDriver->expects($this->any())
            ->method('createStatement')
            ->willReturnCallback(fn() => new StatementContainer());

        return new Adapter(
            $mockDriver, 
            $adapterPlatform,
            new ResultSet()
        );
    }
}
