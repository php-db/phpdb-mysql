<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Mysqli;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Mysqli;
use Laminas\Db\ResultSet\AbstractResultSet;
use Laminas\Db\TableGateway\TableGateway;
use LaminasIntegrationTest\Db\Adapter\Mysql\Container\TestAsset\SetupTrait;
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
        /** @var AdapterInterface $adapter */
        $adapter = $this->getAdapter([
            'db' => [
                'driver'  => Mysqli::class,
                'options' => [
                    'buffer_results' => true,
                ],
            ],
        ]);

        $tableGateway = new TableGateway('test', $adapter);
        /** @var AbstractResultSet $rowset */
        $rowset = $tableGateway->select('id = 0');
        $this->assertEquals(true, $rowset->isBuffered());

        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithoutBufferResult(): void
    {
        /** @var AdapterInterface $adapter */
        $adapter      = $this->getAdapter([
            'db' => [
                'driver'  => 'mysqli',
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
