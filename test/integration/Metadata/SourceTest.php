<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Metadata;

use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\ColumnObject;
use PhpDb\Metadata\Object\ConstraintObject;
use PhpDb\Metadata\Object\TableObject;
use PhpDb\Metadata\Object\ViewObject;
use PhpDb\Mysql\Container\MetadataInterfaceFactory;
use PhpDb\Mysql\Metadata\Source;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function getenv;

#[Group('integration')]
#[Group('integration-mysqli')]
#[CoversMethod(Source::class, 'loadSchemaData')]
#[CoversMethod(Source::class, 'loadTableNameData')]
#[CoversMethod(Source::class, 'loadColumnData')]
#[CoversMethod(Source::class, 'loadConstraintData')]
#[CoversMethod(Source::class, 'loadConstraintDataKeys')]
#[CoversMethod(Source::class, 'loadConstraintReferences')]
#[CoversMethod(Source::class, 'loadTriggerData')]
final class SourceTest extends TestCase
{
    use SetupTrait;

    protected Source $source;

    #[Test]
    public function getColumnNamesReturnsFixtureColumns(): void
    {
        static::assertEqualsCanonicalizing(
            ['id', 'name', 'value'],
            $this->source->getColumnNames('test'),
        );
    }

    #[Test]
    public function getColumnReturnsTypedColumn(): void
    {
        $column = $this->source->getColumn('id', 'test');

        static::assertInstanceOf(ColumnObject::class, $column);
        static::assertSame('id', $column->getName());
        static::assertSame('int', $column->getDataType());
        static::assertFalse($column->getIsNullable());
    }

    #[Test]
    public function getConstraintKeysReturnsPrimaryKeyColumn(): void
    {
        $keys = $this->source->getConstraintKeys('PRIMARY', 'test');

        static::assertCount(1, $keys);
        static::assertSame('id', $keys[0]->getColumnName());
    }

    #[Test]
    public function getConstraintsReturnsPrimaryKey(): void
    {
        $constraints = $this->source->getConstraints('test');

        static::assertContainsOnlyInstancesOf(ConstraintObject::class, $constraints);

        $primary = null;
        foreach ($constraints as $constraint) {
            if ('PRIMARY KEY' !== $constraint->getType()) {
                continue;
            }

            $primary = $constraint;
        }

        static::assertNotNull($primary);
        static::assertSame('_phpdb_test_PRIMARY', $primary->getName());
        static::assertSame(['id'], $primary->getColumns());
    }

    #[Test]
    public function getSchemasContainsCurrentDatabase(): void
    {
        static::assertContains(
            (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
            $this->source->getSchemas(),
        );
    }

    #[Test]
    public function getTableNamesExcludesViewsByDefault(): void
    {
        $tableNames = $this->source->getTableNames();

        static::assertContains('test', $tableNames);
        static::assertContains('test_charset', $tableNames);
        static::assertContains('test_audit_trail', $tableNames);
        static::assertNotContains('test_view', $tableNames);
    }

    #[Test]
    public function getTableNamesIncludesViewsWhenRequested(): void
    {
        static::assertContains('test_view', $this->source->getTableNames(null, true));
    }

    #[Test]
    public function getTableReturnsTableWithColumnsAndConstraints(): void
    {
        $table = $this->source->getTable('test');

        static::assertInstanceOf(TableObject::class, $table);
        static::assertSame('test', $table->getName());
        static::assertCount(3, $table->getColumns());
        static::assertNotSame([], $table->getConstraints());
    }

    #[Test]
    public function getTablesReturnsTableObjects(): void
    {
        $tables = $this->source->getTables();

        static::assertContainsOnlyInstancesOf(TableObject::class, $tables);
        static::assertNotSame([], $tables);
    }

    #[Test]
    public function getTriggerNames(): void
    {
        static::assertContains('after_test_update', $this->source->getTriggerNames());
    }

    #[Test]
    public function getViewNamesAndGetView(): void
    {
        static::assertContains('test_view', $this->source->getViewNames());

        $view = $this->source->getView('test_view');

        static::assertInstanceOf(ViewObject::class, $view);
        static::assertSame('test_view', $view->getName());
    }

    protected function setUp(): void
    {
        $this->getAdapter();

        $factory      = new MetadataInterfaceFactory();
        $this->source = $factory($this->container, MetadataInterface::class);

        parent::setUp();
    }
}
