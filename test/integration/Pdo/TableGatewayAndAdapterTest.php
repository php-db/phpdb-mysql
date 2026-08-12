<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Pdo;

use Exception;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\ResultSet\AbstractResultSet;
use PhpDb\TableGateway\TableGateway;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_fill;

/**
 * Usually mysql has 151 max connections by default.
 * Set up a test where executed PhpDb\Adapter\Adapter::query and then using table gateway to fetch a row
 * On tear down disconnected from the database and set the driver adapter on null
 * Running many tests ended up in consuming all mysql connections and not releasing them
 */
#[CoversMethod(Connection::class, 'disconnect')]
final class TableGatewayAndAdapterTest extends TestCase
{
    use SetupTrait;

    public static function connections(): array
    {
        return array_fill(0, count: 200, value: []);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('connections')]
    public function getOutOfConnections(): void
    {
        $adapter = $this->getAdapter();
        $adapter->query('SELECT VERSION();');
        $table = new TableGateway(
            'test',
            $this->adapter,
        );
        $select = $table->getSql()->select()->where(['name' => 'foo']);
        /** @var AbstractResultSet $result */
        $result = $table->selectWith($select);
        static::assertCount(3, $result->current());
    }

    protected function tearDown(): void
    {
        if ($this->adapter->getDriver()->getConnection()->isConnected()) {
            $this->adapter->getDriver()->getConnection()->disconnect();
        }
        $this->adapter = null;
    }
}
