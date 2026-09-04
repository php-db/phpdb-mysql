<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql\Ddl;

use BackedEnum;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\Mysql\Sql\ColumnFormatEnum;
use PhpDb\Mysql\Sql\Ddl\ColumnOptionTrait;
use PhpDb\Mysql\Sql\Ddl\CreateTableDecorator;
use PhpDb\Mysql\Sql\StorageEnum;
use PhpDb\Sql\Ddl\Column;
use PhpDb\Sql\Ddl\Constraint;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDbTest\Mysql\Sql\Ddl\TestAsset\ColumnOptionMatrix;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;

use function sprintf;
use function strtoupper;

#[CoversMethod(CreateTableDecorator::class, 'setSubject')]
#[CoversMethod(CreateTableDecorator::class, 'processColumns')]
#[CoversTrait(ColumnOptionTrait::class)]
final class CreateTableDecoratorTest extends TestCase
{
    protected AdapterPlatform $platform;

    /** @return array<string, array{array<string, bool|string>, string}> */
    public static function columnOptionMatrixProvider(): array
    {
        return ColumnOptionMatrix::pairedWith([
            'all options' => "CREATE TABLE `test` ( \n    `name` VARCHAR(255) UNSIGNED ZEROFILL CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL AUTO_INCREMENT COMMENT 'here' COLUMN_FORMAT DYNAMIC STORAGE MEMORY \n)",
            'charset collate' => "CREATE TABLE `test` ( \n    `name` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL \n)",
            'format storage' => "CREATE TABLE `test` ( \n    `name` VARCHAR(255) NOT NULL COLUMN_FORMAT FIXED STORAGE DISK \n)",
            'reverse declared' => "CREATE TABLE `test` ( \n    `name` VARCHAR(255) UNSIGNED CHARACTER SET latin1 NOT NULL COMMENT 'c' STORAGE DISK \n)",
            'unknown option' => "CREATE TABLE `test` ( \n    `name` VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL \n)",
            'after only' => "CREATE TABLE `test` ( \n    `name` VARCHAR(255) NOT NULL \n)",
            'falsy skipped' => "CREATE TABLE `test` ( \n    `name` VARCHAR(255) COLLATE utf8mb4_bin NOT NULL \n)",
        ]);
    }

    /** @return array<string, array{string}> */
    public static function keywordOptionProvider(): array
    {
        return [
            'columnformat' => ['columnformat'],
            'storage'      => ['storage'],
        ];
    }

    /** @return array<string, array{string, string, string}> */
    public static function unsafeColumnOptionProvider(): array
    {
        return [
            'charset statement terminator' => ['charset', 'utf8mb3; DROP TABLE users; --', 'charset'],
            'charset quoted value'         => ['charset', "'utf8mb3'", 'charset'],
            'charset backtick'             => ['charset', 'utf8mb3` DEFAULT `', 'charset'],
            'collate statement terminator' => [
                'collate',
                'utf8mb3_unicode_ci; DROP TABLE users; --',
                'collate',
            ],
            'collate trailing clause'      => ['collate', 'utf8mb3_unicode_ci COMMENT "x"', 'collate'],
        ];
    }

    /** @return array<string, array{string, string, class-string<BackedEnum>}> */
    public static function unsafeKeywordOptionProvider(): array
    {
        return [
            'columnformat statement terminator' => [
                'column_format',
                'FIXED; DROP TABLE users; --',
                ColumnFormatEnum::class,
            ],
            'columnformat unknown keyword'      => ['column_format', 'COMPRESSED', ColumnFormatEnum::class],
            'storage statement terminator'      => ['storage', 'DISK; DROP TABLE users; --', StorageEnum::class],
            'storage unknown keyword'           => ['storage', 'TAPE', StorageEnum::class],
        ];
    }

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
    public function columnFormatOptionWithUnderscoreAlias(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('column_format', 'dynamic');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertStringContainsString('COLUMN_FORMAT DYNAMIC', $sql);
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

    /**
     * Pins the exact DDL produced for a matrix of column options.
     *
     * @param array<string, bool|string> $options
     */
    #[Test]
    #[DataProvider('columnOptionMatrixProvider')]
    public function generatesExpectedSqlForColumnOptions(array $options, string $expected): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setNullable(false);

        foreach ($options as $name => $value) {
            $col->setOption($name, $value);
        }

        $table->addColumn($col);

        static::assertSame($expected, $this->buildSql($table));
    }

    #[Test]
    public function optionInsertsBeforeInlinePrimaryKey(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Integer('id');
        $col->addConstraint(new Constraint\PrimaryKey());
        $col->setOption('autoincrement', true);
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertStringContainsString('AUTO_INCREMENT PRIMARY KEY', $sql);
    }

    #[Test]
    public function optionInsertsBeforeInlineReferences(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Integer('other_id');
        $col->addConstraint(new Constraint\ForeignKey('fk_other', 'other_id', 'other', 'id'));
        $col->setOption('comment', 'linked');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        static::assertMatchesRegularExpression("/COMMENT 'linked'.*REFERENCES/s", $sql);
    }

    #[Test]
    #[DataProvider('unsafeColumnOptionProvider')]
    public function rejectsColumnOptionValueThatWouldInjectSql(
        string $option,
        string $value,
        string $reportedOption,
    ): void {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption($option, $value);
        $table->addColumn($col);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid value for the "%s" column option', $reportedOption));

        $this->buildSql($table);
    }

    #[Test]
    #[DataProvider('unsafeKeywordOptionProvider')]
    public function rejectsKeywordOptionValueThatWouldInjectSql(string $option, string $value, string $enum): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption($option, $value);
        $table->addColumn($col);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage(sprintf(
            '"%s" is not a valid backing value for enum %s',
            strtoupper($value),
            $enum,
        ));

        $this->buildSql($table);
    }

    #[Test]
    #[DataProvider('keywordOptionProvider')]
    public function rejectsNonStringKeywordOptionValue(string $option): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption($option, true);
        $table->addColumn($col);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid value for the "%s" column option', $option));
        $this->expectExceptionMessage('received "bool"');

        $this->buildSql($table);
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
    public function tableWithoutColumnsRendersNoColumnDefinitions(): void
    {
        $sql = $this->buildSql(new CreateTable('test'));

        static::assertStringContainsString('CREATE TABLE `test`', $sql);
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
