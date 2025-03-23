<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Mysqli;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Mysql\Adapter;
use Laminas\Db\Mysql\Driver\Mysqli\Driver;
use Laminas\Db\Mysql\Platform;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

final class TableGatewayTest extends TestCase
{
    use TraitSetup;

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithBufferResult()
    {
        $dbConfig = [
            'database' => $this->variables['database'],
            'hostname' => $this->variables['hostname'],
            'username' => $this->variables['username'],
            'password' => $this->variables['password'],
            'options'  => ['buffer_results' => true],
        ];
        /** @var DriverInterface */
        $driver  = $this->getDriverFactory()($dbConfig);
        $adapter = new Adapter(
            $dbConfig,
            $driver,
            new Platform($driver)
        );
        $tableGateway = new TableGateway('test', $adapter);
        $rowset       = $tableGateway->select('id = 0');

        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithoutBufferResult()
    {
        $dbConfig = [
            'driver'   => Driver::class,
            'database' => $this->variables['database'],
            'hostname' => $this->variables['hostname'],
            'username' => $this->variables['username'],
            'password' => $this->variables['password'],
            'options'  => ['buffer_results' => false],
        ];
        $driver  = $this->getDriverFactory()($dbConfig);
        $adapter = new Adapter(
            $dbConfig,
            $driver,
            new Platform($driver)
        );
        $tableGateway = new TableGateway('test', $adapter);
        $rowset       = $tableGateway->select('id = 0');

        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }
}
