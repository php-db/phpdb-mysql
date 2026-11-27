<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql;

use PhpDb\Mysql\Sql\Ddl\AlterTable;
use PhpDb\Mysql\Sql\Ddl\CreateTable;
use PhpDb\Mysql\Sql\Platform;
use PhpDb\Mysql\Sql\Select;
use PhpDb\Sql\Ddl\AlterTable as StandardAlterTable;
use PhpDb\Sql\Ddl\CreateTable as StandardCreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select as StandardSelect;
use PhpDb\Sql\Update;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Platform::class)]
final class PlatformTest extends TestCase
{
    #[Test]
    public function buildsStandardStatementsWhereMysqlRendersThemAsStandardSql(): void
    {
        $platform = new Platform();

        static::assertSame(Insert::class, $platform->insert('test')::class);
        static::assertSame(Update::class, $platform->update('test')::class);
        static::assertSame(Delete::class, $platform->delete('test')::class);
        static::assertSame(DropTable::class, $platform->dropTable('test')::class);
    }

    #[Test]
    public function buildsTheMysqlAlterTable(): void
    {
        $table = (new Platform())->alterTable('test');

        static::assertInstanceOf(AlterTable::class, $table);
        static::assertInstanceOf(StandardAlterTable::class, $table);
    }

    #[Test]
    public function buildsTheMysqlCreateTable(): void
    {
        $table = (new Platform())->createTable('test');

        static::assertInstanceOf(CreateTable::class, $table);
        static::assertInstanceOf(StandardCreateTable::class, $table);
    }

    #[Test]
    public function buildsTheMysqlSelect(): void
    {
        $select = (new Platform())->select('test');

        static::assertInstanceOf(Select::class, $select);
        static::assertInstanceOf(StandardSelect::class, $select);
    }
}
