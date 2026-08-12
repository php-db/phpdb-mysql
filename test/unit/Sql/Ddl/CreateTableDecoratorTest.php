<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql\Ddl;

use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\Mysql\Sql\Ddl\CreateTableDecorator;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\CreateTable;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(CreateTableDecorator::class, 'setSubject')]
#[CoversMethod(CreateTableDecorator::class, 'processColumns')]
#[CoversMethod(CreateTableDecorator::class, 'getSqlInsertOffsets')]
#[CoversMethod(CreateTableDecorator::class, 'compareColumnOptions')]
#[CoversMethod(CreateTableDecorator::class, 'normalizeColumnOption')]
final class CreateTableDecoratorTest extends TestCase
{
    protected AdapterPlatform $platform;

    #[Test]
    public function charsetAppearsAfterUnsigned(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', true);
        $col->setOption('charset', 'utf8mb3');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertMatchesRegularExpression('/UNSIGNED CHARACTER SET utf8mb3/', $sql);
    }

    #[Test]
    public function charsetAppearsBeforeNotNull(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setNullable(false);
        $col->setOption('charset', 'utf8mb3');
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertMatchesRegularExpression(
            '/CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL/',
            $sql,
        );
    }

    #[Test]
    public function columnCharset(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('charset', 'utf8mb3');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertStringContainsString('CHARACTER SET utf8mb3', $sql);
    }

    #[Test]
    public function columnCharsetAndCollate(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('charset', 'utf8mb3');
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertStringContainsString('CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci', $sql);
    }

    #[Test]
    public function columnCollate(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertStringContainsString('COLLATE utf8mb3_unicode_ci', $sql);
    }

    #[Test]
    public function columnFormatOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('columnformat', 'fixed');
        $table->addColumn($col);

        static::assertStringContainsString('COLUMN_FORMAT FIXED', $this->buildSql($table));
    }

    #[Test]
    public function commentOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('comment', 'A test column');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertStringContainsString('COMMENT', $sql);
    }

    #[Test]
    public function falsyOptionIsSkipped(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', false);
        $table->addColumn($col);

        static::assertStringNotContainsString('UNSIGNED', $this->buildSql($table));
    }

    #[Test]
    public function fullColumnDefinition(): void
    {
        $table = new CreateTable('test');

        $id = new Column\Integer('id');
        $id->setOption('unsigned', true);
        $id->setOption('auto_increment', true);
        $table->addColumn($id);

        $name = new Column\Varchar('agency_id', 255);
        $name->setNullable(false);
        $name->setOption('charset', 'utf8mb3');
        $name->setOption('collate', 'utf8mb3_unicode_ci');
        $table->addColumn($name);

        $table->addConstraint(new Constraint\PrimaryKey(['id']));

        $sql = $this->buildSql($table);

        static::assertStringContainsString('UNSIGNED', $sql);
        static::assertStringContainsString('AUTO_INCREMENT', $sql);
        static::assertStringContainsString('CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL', $sql);
    }

    #[Test]
    public function storageOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('storage', 'disk');
        $table->addColumn($col);

        static::assertStringContainsString('STORAGE DISK', $this->buildSql($table));
    }

    #[Test]
    public function unsignedOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', true);
        $col->setOption('auto_increment', true);
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertStringContainsString('UNSIGNED', $sql);
        static::assertStringContainsString('AUTO_INCREMENT', $sql);
    }

    #[Test]
    public function zerofillOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('zerofill', true);
        $table->addColumn($col);

        static::assertStringContainsString('ZEROFILL', $this->buildSql($table));
    }

    protected function setUp(): void
    {
        $driver = new Driver(
            $this->createStub(AbstractPdoConnection::class),
            $this->createStub(Statement::class),
            $this->createStub(Result::class),
        );
        $this->platform = new AdapterPlatform($driver);
    }

    private function buildSql(CreateTable $table): string
    {
        $decorator = new CreateTableDecorator();
        $decorator->setSubject($table);

        return $decorator->getSqlString($this->platform);
    }
}
