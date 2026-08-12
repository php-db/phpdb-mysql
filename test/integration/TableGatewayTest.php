<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql;

use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql\Driver;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\TableGateway\TableGateway;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class TableGatewayTest extends TestCase
{
    use SetupTrait;

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    #[Test]
    public function selectWithEmptyCurrentWithBufferResult(): void
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

        static::assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    #[Test]
    public function selectWithEmptyCurrentWithoutBufferResult(): void
    {
        /** @var AdapterInterface&Adapter $adapter */
        $adapter = $this->getAdapter([
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
        static::assertFalse($rowset->isBuffered());

        static::assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }
}
