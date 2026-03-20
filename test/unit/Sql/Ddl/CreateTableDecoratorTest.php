<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql\Ddl;

use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\Mysql\Sql\Ddl\CreateTableDecorator;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\CreateTable;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(CreateTableDecorator::class, 'processColumns')]
#[CoversMethod(CreateTableDecorator::class, 'getSqlInsertOffsets')]
final class CreateTableDecoratorTest extends TestCase
{
    protected AdapterPlatform $platform;

    protected function setUp(): void
    {
        $driver         = new Driver(
            $this->createMock(Connection::class),
            $this->createMock(Statement::class),
            $this->createMock(Result::class),
        );
        $this->platform = new AdapterPlatform($driver);
    }

    private function buildSql(CreateTable $table): string
    {
        $decorator = new CreateTableDecorator();
        $decorator->setSubject($table);

        return $decorator->getSqlString($this->platform);
    }

    public function testColumnCharset(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('charset', 'utf8mb3');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertStringContainsString('CHARACTER SET utf8mb3', $sql);
    }

    public function testColumnCollate(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertStringContainsString('COLLATE utf8mb3_unicode_ci', $sql);
    }

    public function testColumnCharsetAndCollate(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('charset', 'utf8mb3');
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertStringContainsString('CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci', $sql);
    }

    public function testCharsetAppearsBeforeNotNull(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setNullable(false);
        $col->setOption('charset', 'utf8mb3');
        $col->setOption('collate', 'utf8mb3_unicode_ci');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertMatchesRegularExpression(
            '/CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL/',
            $sql,
        );
    }

    public function testCharsetAppearsAfterUnsigned(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', true);
        $col->setOption('charset', 'utf8mb3');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertMatchesRegularExpression('/UNSIGNED CHARACTER SET utf8mb3/', $sql);
    }

    public function testUnsignedOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', true);
        $col->setOption('auto_increment', true);
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertStringContainsString('UNSIGNED', $sql);
        self::assertStringContainsString('AUTO_INCREMENT', $sql);
    }

    public function testCommentOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('comment', 'A test column');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertStringContainsString('COMMENT', $sql);
    }

    public function testFullColumnDefinition(): void
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

        self::assertStringContainsString('UNSIGNED', $sql);
        self::assertStringContainsString('AUTO_INCREMENT', $sql);
        self::assertStringContainsString('CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL', $sql);
    }
}
