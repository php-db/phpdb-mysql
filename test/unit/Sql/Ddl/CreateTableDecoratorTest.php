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
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDbTest\Mysql\Sql\Ddl\TestAsset\ColumnOptionMatrix;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function sprintf;

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

    public function testColumnFormatOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('column_format', 'dynamic');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertStringContainsString('COLUMN_FORMAT DYNAMIC', $sql);
    }

    public function testStorageOption(): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption('storage', 'disk');
        $table->addColumn($col);

        $sql = $this->buildSql($table);

        self::assertStringContainsString('STORAGE DISK', $sql);
    }

    #[DataProvider('unsafeColumnOptionProvider')]
    public function testRejectsColumnOptionValueThatWouldInjectSql(
        string $option,
        string $value,
        string $reportedOption
    ): void {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setOption($option, $value);
        $table->addColumn($col);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid value for the "%s" column option', $reportedOption));

        $this->buildSql($table);
    }

    /** @return array<string, array{string, string, string}> */
    public static function unsafeColumnOptionProvider(): array
    {
        return [
            'charset statement terminator'      => ['charset', 'utf8mb3; DROP TABLE users; --', 'charset'],
            'charset quoted value'              => ['charset', "'utf8mb3'", 'charset'],
            'charset backtick'                  => ['charset', 'utf8mb3` DEFAULT `', 'charset'],
            'collate statement terminator'      => [
                'collate',
                'utf8mb3_unicode_ci; DROP TABLE users; --',
                'collate',
            ],
            'collate trailing clause'           => ['collate', 'utf8mb3_unicode_ci COMMENT "x"', 'collate'],
            'columnformat statement terminator' => ['column_format', 'FIXED; DROP TABLE users; --', 'columnformat'],
            'columnformat unknown keyword'      => ['column_format', 'COMPRESSED', 'columnformat'],
            'storage statement terminator'      => ['storage', 'DISK; DROP TABLE users; --', 'storage'],
            'storage unknown keyword'           => ['storage', 'TAPE', 'storage'],
        ];
    }

    /**
     * Pins the exact DDL produced for a matrix of column options.
     *
     * @param array<string, bool|string> $options
     */
    #[DataProvider('columnOptionMatrixProvider')]
    public function testGeneratesExpectedSqlForColumnOptions(array $options, string $expected): void
    {
        $table = new CreateTable('test');
        $col   = new Column\Varchar('name', 255);
        $col->setNullable(false);

        foreach ($options as $name => $value) {
            $col->setOption($name, $value);
        }

        $table->addColumn($col);

        self::assertSame($expected, $this->buildSql($table));
    }

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
}
