<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Feature\MetadataFeature;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Stdlib\ArrayObject;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\Assert;

use function count;

#[Attributes\Group('integration')]
#[Attributes\Group('integration-pdo')]
#[Attributes\CoversClass(TableGateway::class)]
#[Attributes\CoversMethod(TableGateway::class, '__construct')]
#[Attributes\CoversMethod(TableGateway::class, 'select')]
#[Attributes\CoversMethod(TableGateway::class, 'insert')]
#[Attributes\CoversMethod(TableGateway::class, 'update')]
final class TableGatewayTest extends TestCase
{
    use AdapterTrait;

    public function testConstructor(): void
    {
        $tableGateway = new TableGateway('test', $this->adapter);
        $this->assertInstanceOf(TableGateway::class, $tableGateway);
    }

    public function testSelect(): void
    {
        $tableGateway = new TableGateway('test', $this->adapter);
        /** @var ResultSet */
        $rowset       = $tableGateway->select();

        $this->assertTrue(count($rowset) > 0);
        /** @var ArrayObject $row */
        foreach ($rowset as $row) {
            $this->assertTrue(isset($row->id));
            $this->assertNotEmpty(isset($row->name));
            $this->assertNotEmpty(isset($row->value));
        }
    }

    public function testInsert(): void
    {
        $tableGateway = new TableGateway('test', $this->adapter);

        $data         = [
            'name'  => 'test_name',
            'value' => 'test_value',
        ];

        $affectedRows = $tableGateway->insert($data);
        $this->assertEquals(1, $affectedRows);

        /** @var ResultSet */
        $rowSet = $tableGateway->select(['id' => $tableGateway->getLastInsertValue()]);
        /** @var ArrayObject $row */
        $row    = $rowSet->current();

        foreach ($data as $key => $value) {
            $this->assertEquals($row->$key, $value);
        }
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/35
     * @see https://github.com/zendframework/zend-db/pull/178
     *
     * @return int
     */
    public function testInsertWithExtendedCharsetFieldName(): int
    {
        $tableGateway = new TableGateway('test_charset', $this->adapter);

        $affectedRows = $tableGateway->insert([
            'field1' => 'test_value1',
            'field2' => 'test_value2',
        ]);
        $this->assertEquals(1, $affectedRows);

        return $tableGateway->getLastInsertValue();
    }

    /**
     * @param mixed $id
     */
    #[Attributes\Depends('testInsertWithExtendedCharsetFieldName')]
    public function testUpdateWithExtendedCharsetFieldName($id): void
    {
        Assert::isInstanceOf($this->adapter, AdapterInterface::class);
        $tableGateway = new TableGateway('test_charset', $this->adapter);

        $data         = [
            'field1' => 'test_value3',
            'field2' => 'test_value4',
        ];
        $affectedRows = $tableGateway->update($data, ['id' => $id]);
        $this->assertEquals(1, $affectedRows);

        /** @var ResultSet */
        $rowSet = $tableGateway->select(['id' => $id]);
        /** @var ArrayObject $row */
        $row    = $rowSet->current();

        foreach ($data as $key => $value) {
            $this->assertEquals($row->$key, $value);
        }
    }

    /**
     * @param string|TableIdentifier|array $table
     */
    #[Attributes\DataProvider('tableProvider')]
    public function testTableGatewayWithMetadataFeature($table): void
    {
        Assert::isInstanceOf($this->adapter, AdapterInterface::class);
        $tableGateway = new TableGateway($table, $this->adapter, new MetadataFeature());

        self::assertInstanceOf(TableGateway::class, $tableGateway);
        self::assertSame($table, $tableGateway->getTable());
    }

    /**
     * @psalm-return array<array-key, array{0: string|TableIdentifier|array}>
     * */
    public static function tableProvider(): array
    {
        return [
            'string'                  => ['test'],
            'aliased_string'          => [['foo' => 'test']],
            'TableIdentifier'         => [new TableIdentifier('test')],
            'aliased_TableIdentifier' => [['foo' => new TableIdentifier('test')]],
        ];
    }
}
