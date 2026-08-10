<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql\Ddl;

use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\Mysql\Sql\Ddl\AlterTableDecorator;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Column;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AlterTableDecorator::class, 'processAddColumns')]
#[CoversMethod(AlterTableDecorator::class, 'processChangeColumns')]
#[CoversMethod(AlterTableDecorator::class, 'getSqlInsertOffsets')]
final class AlterTableDecoratorTest extends TestCase
{
    protected AdapterPlatform $platform;

    #[Test]
    public function addColumnAfter(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('after', 'id');
        $alter->addColumn($col);

        $sql = $this->buildSql($alter);

        static::assertStringContainsString('AFTER `id`', $sql);
    }

    #[Test]
    public function addColumnCharset(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('charset', 'utf8mb3');
        $alter->addColumn($col);

        $sql = $this->buildSql($alter);

        static::assertStringContainsString('CHARACTER SET utf8mb3', $sql);
    }

    #[Test]
    public function addColumnCharsetAndCollate(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('charset', 'utf8mb3');
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $alter->addColumn($col);

        $sql = $this->buildSql($alter);

        static::assertStringContainsString('CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci', $sql);
    }

    #[Test]
    public function addColumnCharsetBeforeNotNull(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setNullable(false);
        $col->setOption('charset', 'utf8mb3');
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $alter->addColumn($col);

        $sql = $this->buildSql($alter);

        static::assertMatchesRegularExpression(
            '/CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL/',
            $sql,
        );
    }

    #[Test]
    public function addColumnCollate(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $alter->addColumn($col);

        $sql = $this->buildSql($alter);

        static::assertStringContainsString('COLLATE utf8mb3_unicode_ci', $sql);
    }

    #[Test]
    public function addColumnUnsigned(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', true);
        $col->setOption('auto_increment', true);
        $alter->addColumn($col);

        $sql = $this->buildSql($alter);

        static::assertStringContainsString('UNSIGNED', $sql);
        static::assertStringContainsString('AUTO_INCREMENT', $sql);
    }

    #[Test]
    public function changeColumnCharset(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('charset', 'utf8mb3');
        $alter->changeColumn('name', $col);

        $sql = $this->buildSql($alter);

        static::assertStringContainsString('CHARACTER SET utf8mb3', $sql);
    }

    #[Test]
    public function changeColumnCharsetAndCollate(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setNullable(false);
        $col->setOption('charset', 'utf8mb3');
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $alter->changeColumn('name', $col);

        $sql = $this->buildSql($alter);

        static::assertMatchesRegularExpression(
            '/CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL/',
            $sql,
        );
    }

    #[Test]
    public function changeColumnCollate(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $alter->changeColumn('name', $col);

        $sql = $this->buildSql($alter);

        static::assertStringContainsString('COLLATE utf8mb3_unicode_ci', $sql);
    }

    protected function setUp(): void
    {
        $driver = new Driver(
            $this->createMock(Connection::class),
            $this->createMock(Statement::class),
            $this->createMock(Result::class),
        );
        $this->platform = new AdapterPlatform($driver);
    }

    private function buildSql(AlterTable $table): string
    {
        $decorator = new AlterTableDecorator();
        $decorator->setSubject($table);

        return $decorator->getSqlString($this->platform);
    }
}
