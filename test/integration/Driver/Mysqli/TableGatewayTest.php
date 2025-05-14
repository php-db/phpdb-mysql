<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Mysqli;

use Laminas\Db\Adapter\Mysql\Adapter;
use Laminas\Db\ResultSet\AbstractResultSet;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractResultSet::class, 'current')]
#[CoversMethod(AbstractResultSet::class, 'isBuffered')]
#[CoversMethod(TableGateway::class, 'select')]
final class TableGatewayTest extends TestCase
{
    use TraitSetup;

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithBufferResult(): void
    {
        $adapter      = new Adapter([
            'driver'   => 'mysqli',
            'database' => $this->variables['database'],
            'hostname' => $this->variables['hostname'],
            'username' => $this->variables['username'],
            'password' => $this->variables['password'],
            'options'  => ['buffer_results' => true],
        ]);
        $tableGateway = new TableGateway('test', $adapter);
        $rowset       = $tableGateway->select('id = 0');
        $this->assertEquals(true, $rowset->isBuffered());

        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithoutBufferResult(): void
    {
        $adapter      = new Adapter([
            'driver'   => 'mysqli',
            'database' => $this->variables['database'],
            'hostname' => $this->variables['hostname'],
            'username' => $this->variables['username'],
            'password' => $this->variables['password'],
            'options'  => ['buffer_results' => false],
        ]);
        $tableGateway = new TableGateway('test', $adapter);
        $rowset       = $tableGateway->select('id = 0');
        $this->assertEquals(false, $rowset->isBuffered());

        /** @todo Have resultset implememt Iterator */
        /** @psalm-suppress UndefinedInterfaceMethod */
        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }
}
