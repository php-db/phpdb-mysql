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
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function count;

#[CoversMethod(TableGateway::class, '__construct')]
#[CoversMethod(TableGateway::class, 'select')]
#[CoversMethod(TableGateway::class, 'insert')]
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

    public function testConstructor(): void
    {
        /** @var AdapterInterface&Adapter $adapter */
        $adapter      = $this->getAdapter(['db' => ['driver' => Driver::class]]);
        $tableGateway = new TableGateway('test', $adapter);
        $this->assertInstanceOf(TableGateway::class, $tableGateway);
    }

    public function testInsert(): void
    {
        $tableGateway = new TableGateway('test', $this->getAdapter(['db' => ['driver' => Driver::class]]));

        $tableGateway->select();
        $data = [
            'name'  => 'test_name',
            'value' => 'test_value',
        ];
        $affectedRows = $tableGateway->insert($data);
        $this->assertEquals(1, $affectedRows);
        /** @var ResultSet $rowSet */
        $rowSet = $tableGateway->select(['id' => $tableGateway->getLastInsertValue()]);
        /** @var ArrayObject $row */
        $row = $rowSet->current();

        foreach ($data as $key => $value) {
            $this->assertEquals($row->$key, $value);
        }
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/35
     * @see https://github.com/zendframework/zend-db/pull/178
     */
    public function testInsertWithExtendedCharsetFieldName(): int|string
    {
        $tableGateway = new TableGateway('test_charset', $this->getAdapter(['db' => ['driver' => Driver::class]]));

        $affectedRows = $tableGateway->insert([
            'field$' => 'test_value1',
            'field_' => 'test_value2',
        ]);
        $this->assertEquals(1, $affectedRows);

        return $tableGateway->getLastInsertValue();
    }

    public function testSelect(): void
    {
        $tableGateway = new TableGateway('test', $this->getAdapter(['db' => ['driver' => Driver::class]]));
        /** @var ResultSet $rowset */
        $rowset = $tableGateway->select();
        $this->assertTrue(count($rowset) > 0);
        /** @var ArrayObject $row */
        foreach ($rowset as $row) {
            $this->assertTrue(isset($row->id));
            $this->assertNotEmpty(isset($row->name));
            $this->assertNotEmpty(isset($row->value));
        }
    }

    #[DataProvider('tableProvider')]
    public function testTableGatewayWithMetadataFeature(array|string|TableIdentifier $table): void
    {
        /** @var AdapterInterface&SchemaAwareInterface&Adapter $adapter */
        $adapter      = $this->getAdapter(['db' => ['driver' => Driver::class]]);
        $tableGateway = new TableGateway(
            $table,
            $adapter,
            new MetadataFeature(
                new Source($adapter),
            ),
        );

        self::assertInstanceOf(TableGateway::class, $tableGateway);
        self::assertSame($table, $tableGateway->getTable());
    }

    #[Depends('testInsertWithExtendedCharsetFieldName')]
    public function testUpdateWithExtendedCharsetFieldName(mixed $id): void
    {
        $tableGateway = new TableGateway('test_charset', $this->getAdapter(['db' => ['driver' => Driver::class]]));

        $data = [
            'field$' => 'test_value3',
            'field_' => 'test_value4',
        ];
        $affectedRows = $tableGateway->update($data, ['id' => $id]);
        $this->assertEquals(1, $affectedRows);
        /** @var ResultSet $rowSet */
        $rowSet = $tableGateway->select(['id' => $id]);
        /** @var ArrayObject $row */
        $row = $rowSet->current();

        foreach ($data as $key => $value) {
            $this->assertEquals($row->$key, $value);
        }
    }
}
