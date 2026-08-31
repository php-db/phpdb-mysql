<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Pdo;

use Laminas\Stdlib\ArrayObject;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Mysql\Metadata\Source;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\TableIdentifier;
use PhpDb\TableGateway\Feature\MetadataFeature;
use PhpDb\TableGateway\TableGateway;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;

#[CoversNothing]
final class TableGatewayTest extends TestCase
{
    use SetupTrait;

    /** @psalm-return array<string, array{0: mixed}> */
    public static function tableProvider(): array
    {
        return [
            'string'                  => ['test'],
            'aliased string'          => [['foo' => 'test']],
            'TableIdentifier'         => [new TableIdentifier('test')],
            'aliased TableIdentifier' => [['foo' => new TableIdentifier('test')]],
        ];
    }

    #[Test]
    public function constructor(): void
    {
        /** @var AdapterInterface&Adapter $adapter */
        $adapter      = $this->getAdapter([AdapterInterface::class => ['driver' => Driver::class]]);
        $tableGateway = new TableGateway('test', $adapter);
        static::assertInstanceOf(TableGateway::class, $tableGateway);
    }

    #[Test]
    public function insert(): void
    {
        $tableGateway = new TableGateway('test', $this->getAdapter([
            AdapterInterface::class => ['driver' => Driver::class],
        ]));

        $tableGateway->select();
        $data = [
            'name'  => 'test_name',
            'value' => 'test_value',
        ];
        $affectedRows = $tableGateway->insert($data);
        static::assertSame(1, $affectedRows);
        /** @var ResultSet $rowSet */
        $rowSet = $tableGateway->select(['id' => $tableGateway->getLastInsertValue()]);
        /** @var ArrayObject $row */
        $row = $rowSet->current();

        foreach ($data as $key => $value) {
            static::assertEquals($row->$key, $value);
        }
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/35
     * @see https://github.com/zendframework/zend-db/pull/178
     */
    #[Test]
    public function insertWithExtendedCharsetFieldName(): int|string
    {
        $tableGateway = new TableGateway('test_charset', $this->getAdapter([
            AdapterInterface::class => ['driver' => Driver::class],
        ]));

        $affectedRows = $tableGateway->insert([
            'field$' => 'test_value1',
            'field_' => 'test_value2',
        ]);
        static::assertSame(1, $affectedRows);

        return $tableGateway->getLastInsertValue();
    }

    #[Test]
    public function select(): void
    {
        $tableGateway = new TableGateway('test', $this->getAdapter([
            AdapterInterface::class => ['driver' => Driver::class],
        ]));
        /** @var ResultSet $rowset */
        $rowset = $tableGateway->select();
        static::assertTrue(count($rowset) > 0);
        /** @var ArrayObject $row */
        foreach ($rowset as $row) {
            static::assertTrue(null !== ($row->id ?? null));
            static::assertNotEmpty(null !== ($row->name ?? null));
            static::assertNotEmpty(null !== ($row->value ?? null));
        }
    }

    #[Test]
    #[DataProvider('tableProvider')]
    public function tableGatewayWithMetadataFeature(array|string|TableIdentifier $table): void
    {
        /** @var AdapterInterface&SchemaAwareInterface&Adapter $adapter */
        $adapter      = $this->getAdapter([AdapterInterface::class => ['driver' => Driver::class]]);
        $tableGateway = new TableGateway(
            $table,
            $adapter,
            new MetadataFeature(
                new Source($adapter),
            ),
        );

        static::assertInstanceOf(TableGateway::class, $tableGateway);
        static::assertSame($table, $tableGateway->getTable());
    }

    #[Test]
    #[Depends('insertWithExtendedCharsetFieldName')]
    public function updateWithExtendedCharsetFieldName(mixed $id): void
    {
        $tableGateway = new TableGateway('test_charset', $this->getAdapter([
            AdapterInterface::class => ['driver' => Driver::class],
        ]));

        $data = [
            'field$' => 'test_value3',
            'field_' => 'test_value4',
        ];
        $affectedRows = $tableGateway->update($data, ['id' => $id]);
        static::assertSame(1, $affectedRows);
        /** @var ResultSet $rowSet */
        $rowSet = $tableGateway->select(['id' => $id]);
        /** @var ArrayObject $row */
        $row = $rowSet->current();

        foreach ($data as $key => $value) {
            static::assertEquals($row->$key, $value);
        }
    }
}
