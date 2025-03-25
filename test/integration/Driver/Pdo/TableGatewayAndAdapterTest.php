<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Pdo;

use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

use function array_fill;

/**
 * Usually mysql has 151 max connections by default.
 * Set up a test where executed Laminas\Db\Adapter\Adapter::query and then using table gateway to fetch a row
 * On tear down disconnected from the database and set the driver adapter on null
 * Running many tests ended up in consuming all mysql connections and not releasing them
 */
final class TableGatewayAndAdapterTest extends TestCase
{
    use AdapterTrait;

    /**
     * @dataProvider connections
     */
    public function testGetOutOfConnections(): void
    {
        $this->adapter->query('SELECT VERSION();');
        $table  = new TableGateway(
            'test',
            $this->adapter
        );
        /** @var Select */
        $select = $table->getSql()->select()->where(['name' => 'foo']);
        /** @var ResultSet */
        $result = $table->selectWith($select);
        /** @psalm-suppress PossiblyNullArgument */
        self::assertCount(3, $result->current());
    }

    protected function tearDown(): void
    {
        if ($this->adapter->getDriver()->getConnection()->isConnected()) {
            $this->adapter->getDriver()->getConnection()->disconnect();
        }
        unset($this->adapter);
    }

    /**
     * @psalm-return array<int, array<int, array>>
     */
    public static function connections(): array
    {
        return array_fill(0, 200, []);
    }
}
