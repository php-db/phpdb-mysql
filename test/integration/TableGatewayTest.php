<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql\Driver;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\TableGateway\TableGateway;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(AbstractResultSet::class, 'current')]
#[CoversMethod(AbstractResultSet::class, 'isBuffered')]
#[CoversMethod(TableGateway::class, 'select')]
final class TableGatewayTest extends TestCase
{
    use SetupTrait;

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithBufferResult(): void
    {
        /** @var AdapterInterface&Adapter $adapter */
        $adapter = $this->getAdapter([
            'db' => [
                'driver'  => Driver::class,
                'options' => [
                    'buffer_results' => true,
                ],
            ],
        ]);

        $tableGateway = new TableGateway('test', $adapter);
        /** @var AbstractResultSet $rowset */
        $rowset = $tableGateway->select('id = 0');

        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithoutBufferResult(): void
    {
        /** @var AdapterInterface&Adapter $adapter */
        $adapter      = $this->getAdapter([
            'db' => [
                'driver'  => Driver::class,
                'options' => [
                    'buffer_results' => false,
                ],
            ],
        ]);
        $tableGateway = new TableGateway('test', $adapter);
        /** @var AbstractResultSet $rowset */
        $rowset = $tableGateway->select('id = 0');
        $this->assertEquals(false, $rowset->isBuffered());

        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }
}
