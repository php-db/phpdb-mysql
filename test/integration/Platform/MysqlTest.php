<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Platform;

use mysqli;
use Override;
use PhpDb\Adapter\Driver\Pdo;
use PhpDb\Mysql\AdapterPlatform as Mysql;
use PhpDb\Mysql\Connection as MysqliConnection;
use PhpDb\Mysql\Driver as MysqliDriver;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver as PdoDriver;
use PhpDb\Mysql\Result as MysqliResult;
use PhpDb\Mysql\Statement as MysqliStatement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function getenv;

#[Group('integration')]
#[CoversMethod(MysqliDriver::class, 'quoteValue')]
#[CoversMethod(PdoDriver::class, 'quoteValue')]
final class MysqlTest extends TestCase
{
    /** @var array<string, mysqli|\PDO> */
    public array $adapters = [];

    protected array $mysqliParams;

    protected array $pdoParams;

    #[Override]
    protected function setUp(): void
    {
        if (getenv('TESTS_PHPDB_ADAPTER_MYSQL') !== 'true') {
            self::markTestSkipped('Integration tests require TESTS_PHPDB_ADAPTER_MYSQL=true');
        }

        if (extension_loaded('mysqli')) {
            $this->mysqliParams = [
                'hostname' => getenv('TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME'),
                'username' => getenv('TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
                'password' => getenv('TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
                'database' => getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
            ];

            $this->adapters['mysqli'] = new mysqli(
                $this->mysqliParams['hostname'],
                $this->mysqliParams['username'],
                $this->mysqliParams['password'],
                $this->mysqliParams['database']
            );
        }

        if (extension_loaded('pdo')) {
            $this->pdoParams = [
                'hostname' => getenv('TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME'),
                'username' => getenv('TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
                'password' => getenv('TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
                'database' => getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
            ];

            $this->adapters['pdo_mysql'] = new \PDO(
                'mysql:host=' . $this->pdoParams['hostname'] . ';dbname='
                . $this->pdoParams['database'],
                $this->pdoParams['username'],
                $this->pdoParams['password']
            );
        }
    }

    /**
     * @return void
     */
    public function testQuoteValueWithMysqli()
    {
        if (! $this->adapters['mysqli'] instanceof \Mysqli) {
            $this->markTestSkipped('MySQL (Mysqli) not configured in unit test configuration file');
        }
        $mysql = new Mysql($this->adapters['mysqli']);
        $value = $mysql->quoteValue('value');
        self::assertEquals('\'value\'', $value);

        $mysql = new Mysql(
            new MysqliDriver(
                new MysqliConnection($this->mysqliParams),
                new MysqliStatement(),
                new MysqliResult()
            )
        );
        $value = $mysql->quoteValue('value');
        self::assertEquals('\'value\'', $value);
    }

    /**
     * @return void
     */
    public function testQuoteValueWithPdoMysql()
    {
        if (! $this->adapters['pdo_mysql'] instanceof \PDO) {
            $this->markTestSkipped('MySQL (PDO_Mysql) not configured in unit test configuration file');
        }
        $mysql = new Mysql($this->adapters['pdo_mysql']);
        $value = $mysql->quoteValue('value');
        self::assertEquals('\'value\'', $value);

        $mysql = new Mysql(
            new PdoDriver(
                new Connection($this->pdoParams),
                new Pdo\Statement(),
                new Pdo\Result()
            )
        );
        $value = $mysql->quoteValue('value');
        self::assertEquals('\'value\'', $value);
    }
}
