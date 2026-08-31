<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql;

use PhpDb\Mysql\Sql\Ddl\AlterTableDecorator;
use PhpDb\Mysql\Sql\Ddl\CreateTableDecorator;
use PhpDb\Mysql\Sql\Platform;
use PhpDb\Mysql\Sql\SelectDecorator;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Platform::class)]
final class PlatformTest extends TestCase
{
    #[Test]
    public function registersAlterTableDecorator(): void
    {
        $platform = new Platform();

        static::assertInstanceOf(AlterTableDecorator::class, $platform->getTypeDecorator(new AlterTable('test')));
    }

    #[Test]
    public function registersCreateTableDecorator(): void
    {
        $platform = new Platform();

        static::assertInstanceOf(CreateTableDecorator::class, $platform->getTypeDecorator(new CreateTable('test')));
    }

    #[Test]
    public function registersSelectDecorator(): void
    {
        $platform = new Platform();

        static::assertInstanceOf(SelectDecorator::class, $platform->getTypeDecorator(new Select('test')));
    }
}
