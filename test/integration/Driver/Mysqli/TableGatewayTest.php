<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Mysqli;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Mysql\Adapter;
use Laminas\Db\Mysql\Driver\Mysqli\Driver;
use Laminas\Db\Mysql\Platform;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
#[Attributes\CoversClass(TableGateway::class)]
#[Attributes\CoversMethod(TableGateway::class, '__construct')]
final class TableGatewayTest extends TestCase
{
    use TraitSetup;

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithBufferResult(): void
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
        /** @var ResultSet */
        $rowset = $tableGateway->select('id = 0');

        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/330
     */
    public function testSelectWithEmptyCurrentWithoutBufferResult(): void
    {
        $dbConfig = [
            'driver'   => Driver::class,
            'database' => $this->variables['database'],
            'hostname' => $this->variables['hostname'],
            'username' => $this->variables['username'],
            'password' => $this->variables['password'],
            'options'  => ['buffer_results' => false],
        ];
        /** @var DriverInterface */
        $driver  = $this->getDriverFactory()($dbConfig);
        $adapter = new Adapter(
            $dbConfig,
            $driver,
            new Platform($driver)
        );
        $tableGateway = new TableGateway('test', $adapter);
        /** @var ResultSet */
        $rowset       = $tableGateway->select('id = 0');

        $this->assertNull($rowset->current());

        $adapter->getDriver()->getConnection()->disconnect();
    }
}
