<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql\Ddl;

use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\Mysql\Sql\Ddl\AlterTableDecorator;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDbTest\Mysql\Sql\Ddl\TestAsset\ColumnOptionMatrix;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversMethod(AlterTableDecorator::class, 'setSubject')]
#[CoversMethod(AlterTableDecorator::class, 'processAddColumns')]
#[CoversMethod(AlterTableDecorator::class, 'processChangeColumns')]
#[CoversMethod(AlterTableDecorator::class, 'getSqlInsertOffsets')]
#[CoversMethod(AlterTableDecorator::class, 'compareColumnOptions')]
#[CoversMethod(AlterTableDecorator::class, 'normalizeColumnOption')]
final class AlterTableDecoratorTest extends TestCase
{
    protected AdapterPlatform $platform;

    /** @return array<string, array{array<string, bool|string>, string}> */
    public static function addColumnMatrixProvider(): array
    {
        return ColumnOptionMatrix::pairedWith([
            'all options' => "ALTER TABLE `test`\n ADD COLUMN `name` VARCHAR(255) UNSIGNED ZEROFILL CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL AUTO_INCREMENT COMMENT 'here' COLUMN_FORMAT DYNAMIC STORAGE MEMORY AFTER `id`",
            'charset collate' => "ALTER TABLE `test`\n ADD COLUMN `name` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL",
            'format storage' => "ALTER TABLE `test`\n ADD COLUMN `name` VARCHAR(255) NOT NULL COLUMN_FORMAT FIXED STORAGE DISK",
            'reverse declared' => "ALTER TABLE `test`\n ADD COLUMN `name` VARCHAR(255) UNSIGNED CHARACTER SET latin1 NOT NULL COMMENT 'c' STORAGE DISK",
            'unknown option' => "ALTER TABLE `test`\n ADD COLUMN `name` VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL",
            'after only' => "ALTER TABLE `test`\n ADD COLUMN `name` VARCHAR(255) NOT NULL AFTER `other_col`",
            'falsy skipped' => "ALTER TABLE `test`\n ADD COLUMN `name` VARCHAR(255) COLLATE utf8mb4_bin NOT NULL",
        ]);
    }

    /** @return array<string, array{array<string, bool|string>, string}> */
    public static function changeColumnMatrixProvider(): array
    {
        return ColumnOptionMatrix::pairedWith([
            'all options' => "ALTER TABLE `test`\n  CHANGE COLUMN `name` `name` VARCHAR(255) UNSIGNED ZEROFILL CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL AUTO_INCREMENT COMMENT 'here' COLUMN_FORMAT DYNAMIC STORAGE MEMORY",
            'charset collate' => "ALTER TABLE `test`\n  CHANGE COLUMN `name` `name` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL",
            'format storage' => "ALTER TABLE `test`\n  CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL COLUMN_FORMAT FIXED STORAGE DISK",
            'reverse declared' => "ALTER TABLE `test`\n  CHANGE COLUMN `name` `name` VARCHAR(255) UNSIGNED CHARACTER SET latin1 NOT NULL COMMENT 'c' STORAGE DISK",
            'unknown option' => "ALTER TABLE `test`\n  CHANGE COLUMN `name` `name` VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL",
            'after only' => "ALTER TABLE `test`\n  CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL",
            'falsy skipped' => "ALTER TABLE `test`\n  CHANGE COLUMN `name` `name` VARCHAR(255) COLLATE utf8mb4_bin NOT NULL",
        ]);
    }

    /** @return array<string, array{string, string, string}> */
    public static function unsafeColumnOptionProvider(): array
    {
        return [
            'charset statement terminator'      => ['charset', 'utf8mb3; DROP TABLE users; --', 'charset'],
            'charset quoted value'              => ['charset', "'utf8mb3'", 'charset'],
            'collate statement terminator'      => [
                'collate',
                'utf8mb3_unicode_ci; DROP TABLE users; --',
                'collate',
            ],
            'columnformat statement terminator' => ['column_format', 'FIXED; DROP TABLE users; --', 'columnformat'],
            'columnformat unknown keyword'      => ['column_format', 'COMPRESSED', 'columnformat'],
            'storage statement terminator'      => ['storage', 'DISK; DROP TABLE users; --', 'storage'],
            'storage unknown keyword'           => ['storage', 'TAPE', 'storage'],
        ];
    }

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
    public function addColumnComment(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('comment', 'A comment');
        $alter->addColumn($col);

        static::assertStringContainsString('COMMENT', $this->buildSql($alter));
    }

    #[Test]
    public function addColumnFalsyOptionSkipped(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', false);
        $alter->addColumn($col);

        static::assertStringNotContainsString('UNSIGNED', $this->buildSql($alter));
    }

    #[Test]
    public function addColumnFormat(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('columnformat', 'fixed');
        $alter->addColumn($col);

        static::assertStringContainsString('COLUMN_FORMAT FIXED', $this->buildSql($alter));
    }

    #[Test]
    #[DataProvider('unsafeColumnOptionProvider')]
    public function addColumnRejectsOptionValueThatWouldInjectSql(
        string $option,
        string $value,
        string $reportedOption,
    ): void {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption($option, $value);
        $alter->addColumn($col);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid value for the "%s" column option', $reportedOption));

        $this->buildSql($alter);
    }

    #[Test]
    public function addColumnStorage(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('storage', 'disk');
        $alter->addColumn($col);

        static::assertStringContainsString('STORAGE DISK', $this->buildSql($alter));
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
    public function addColumnZerofill(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('zerofill', true);
        $alter->addColumn($col);

        static::assertStringContainsString('ZEROFILL', $this->buildSql($alter));
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

    #[Test]
    public function changeColumnComment(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('comment', 'A comment');
        $alter->changeColumn('name', $col);

        static::assertStringContainsString('COMMENT', $this->buildSql($alter));
    }

    #[Test]
    public function changeColumnFalsyOptionSkipped(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', false);
        $alter->changeColumn('id', $col);

        static::assertStringNotContainsString('UNSIGNED', $this->buildSql($alter));
    }

    #[Test]
    public function changeColumnFormat(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('columnformat', 'fixed');
        $alter->changeColumn('name', $col);

        static::assertStringContainsString('COLUMN_FORMAT FIXED', $this->buildSql($alter));
    }

    #[Test]
    public function changeColumnIdentity(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('identity', true);
        $alter->changeColumn('id', $col);

        static::assertStringContainsString('AUTO_INCREMENT', $this->buildSql($alter));
    }

    #[Test]
    #[DataProvider('unsafeColumnOptionProvider')]
    public function changeColumnRejectsOptionValueThatWouldInjectSql(
        string $option,
        string $value,
        string $reportedOption,
    ): void {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption($option, $value);
        $alter->changeColumn('name', $col);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid value for the "%s" column option', $reportedOption));

        $this->buildSql($alter);
    }

    #[Test]
    public function changeColumnStorage(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('storage', 'disk');
        $alter->changeColumn('name', $col);

        static::assertStringContainsString('STORAGE DISK', $this->buildSql($alter));
    }

    #[Test]
    public function changeColumnUnsigned(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('unsigned', true);
        $alter->changeColumn('id', $col);

        static::assertStringContainsString('UNSIGNED', $this->buildSql($alter));
    }

    #[Test]
    public function changeColumnZerofill(): void
    {
        $alter = new AlterTable('test');
        $col   = new Column\Integer('id');
        $col->setOption('zerofill', true);
        $alter->changeColumn('id', $col);

        static::assertStringContainsString('ZEROFILL', $this->buildSql($alter));
    }

    /**
     * Pins the exact DDL produced for a matrix of column options on an added column.
     *
     * @param array<string, bool|string> $options
     */
    #[Test]
    #[DataProvider('addColumnMatrixProvider')]
    public function generatesExpectedSqlForAddedColumnOptions(array $options, string $expected): void
    {
        $alter = new AlterTable('test');
        $alter->addColumn($this->makeColumn($options));

        static::assertSame($expected, $this->buildSql($alter));
    }

    /**
     * Pins the exact DDL produced for a matrix of column options on a changed column.
     *
     * @param array<string, bool|string> $options
     */
    #[Test]
    #[DataProvider('changeColumnMatrixProvider')]
    public function generatesExpectedSqlForChangedColumnOptions(array $options, string $expected): void
    {
        $alter = new AlterTable('test');
        $alter->changeColumn('name', $this->makeColumn($options));

        static::assertSame($expected, $this->buildSql($alter));
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

    private function buildSql(AlterTable $table): string
    {
        $decorator = new AlterTableDecorator();
        $decorator->setSubject($table);

        return $decorator->getSqlString($this->platform);
    }

    /** @param array<string, bool|string> $options */
    private function makeColumn(array $options): Column\Varchar
    {
        $col = new Column\Varchar('name', 255);
        $col->setNullable(false);

        foreach ($options as $name => $value) {
            $col->setOption($name, $value);
        }

        return $col;
    }
}
