<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Mysqli;

use mysqli;
use mysqli_result;
use mysqli_stmt;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Exception\InvalidQueryException;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Result;
use PhpDb\Mysql\Statement;
use PhpDb\ResultSet\ResultSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function getenv;
use function is_int;
use function iterator_to_array;
use function mysqli_report;
use function usleep;

use const MYSQLI_REPORT_ERROR;
use const MYSQLI_REPORT_OFF;
use const MYSQLI_REPORT_STRICT;

#[Group('integration')]
#[Group('integration-mysqli')]
#[CoversClass(Statement::class)]
#[CoversClass(Result::class)]
#[CoversClass(Driver::class)]
final class StatementResultTest extends TestCase
{
    #[Test]
    public function bindsDoubleAndNullParameterTypes(): void
    {
        $container = new ParameterContainer();
        $container->offsetSet('id', 1.5, ParameterContainer::TYPE_DOUBLE);
        $container->offsetSet('name', null, ParameterContainer::TYPE_NULL);

        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE id IN (?, ?)')
            ->execute($container);

        static::assertNotNull($result);
        static::assertTrue($result->isQueryResult());
    }

    #[Test]
    public function bufferAfterIterationStartedThrows(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);
        $result->current();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot buffer a result set that has started iteration.');
        $result->buffer();
    }

    #[Test]
    public function bufferedStatementResultSupportsCountAndRewind(): void
    {
        $driver    = $this->createDriver(true);
        $statement = $driver->createStatement('SELECT * FROM test WHERE value = ?');

        static::assertFalse($statement->isPrepared());
        static::assertSame('SELECT * FROM test WHERE value = ?', $statement->getSql());

        $result = $statement->execute($this->createParameterContainer(['bar']));
        static::assertNotNull($result);

        static::assertTrue($result->isBuffered());
        static::assertTrue($result->isQueryResult());
        static::assertSame(3, $result->getFieldCount());
        static::assertSame(3, $result->count());

        static::assertCount(3, iterator_to_array($result));

        $result->rewind();
        static::assertSame(['id' => 1, 'name' => 'foo', 'value' => 'bar'], $result->current());
    }

    #[Test]
    public function bufferUnbufferedResult(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);
        static::assertFalse($result->isBuffered());

        $result->buffer();

        static::assertTrue($result->isBuffered());
    }

    #[Test]
    public function connectionExecuteUsesMysqliResult(): void
    {
        $mysqli     = $this->createMysqli();
        $connection = new Connection($mysqli);
        new Driver($connection, new Statement(), new Result());

        $result = $connection->execute('SELECT * FROM test WHERE id = 1');
        static::assertNotNull($result);

        static::assertTrue($result->isBuffered());
        static::assertTrue($result->isQueryResult());
        static::assertInstanceOf(mysqli_result::class, $result->getResource());
        static::assertSame(3, $result->getFieldCount());
        static::assertSame(1, $result->count());
        static::assertSame(1, $result->getAffectedRows());

        static::assertCount(1, iterator_to_array($result));

        $result->rewind();
        static::assertSame(['id' => '1', 'name' => 'foo', 'value' => 'bar'], $result->current());
    }

    #[Test]
    public function countOnNonQueryResultThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot count rows in a result that is not a query result');

        $this->executeNonQuery()->count();
    }

    #[Test]
    public function createStatementConnectsTheConnection(): void
    {
        $connection = new Connection($this->connectionParameters());
        $driver     = new Driver($connection, new Statement(), new Result());

        $driver->createStatement('SELECT 1');

        static::assertTrue($connection->isConnected());
    }

    #[Test]
    public function createStatementFromMysqliStmtResource(): void
    {
        $mysqli = $this->createMysqli();
        $driver = new Driver(new Connection($mysqli), new Statement(), new Result());

        $resource = $mysqli->prepare('SELECT * FROM test WHERE id = ?');
        static::assertInstanceOf(mysqli_stmt::class, $resource);

        $statement = $driver->createStatement($resource);

        static::assertSame($resource, $statement->getResource());
        static::assertTrue($statement->isPrepared());
    }

    #[Test]
    public function currentOnNonQueryResultThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot fetch from a result that is not a mysqli_result');

        $this->executeNonQuery()->current();
    }

    #[Test]
    public function executeFailingPreparedStatementThrows(): void
    {
        $statement = $this->createDriver(false)
            ->createStatement('INSERT INTO test (id, name, value) VALUES (?, ?, ?)');

        mysqli_report(MYSQLI_REPORT_OFF);
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Duplicate entry');
            $statement->execute($this->createParameterContainer([1, 'dup', 'dup']));
        } finally {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }
    }

    #[Test]
    public function executeWithEmptyArray(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT 1')
            ->execute([]);

        static::assertNotNull($result);
        static::assertTrue($result->isQueryResult());
    }

    #[Test]
    public function fetchFailureMidIterationThrows(): void
    {
        $victim = $this->createMysqli();
        $killer = $this->createMysqli();

        $driver = new Driver(new Connection($victim), new Statement(bufferResults: false), new Result());
        $result = $driver->createStatement(
            "SELECT REPEAT('x', 65536) AS filler FROM test t1 JOIN test t2 JOIN test t3 JOIN test t4",
        )
            ->execute([]);

        static::assertNotNull($result);
        static::assertNotNull($result->current());

        $killer->query("KILL {$victim->thread_id}");
        usleep(200_000);

        mysqli_report(MYSQLI_REPORT_OFF);
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessageMatches('/gone away|Lost connection/i');
            while ($result->valid()) {
                $result->next();
                $result->current();
            }
        } finally {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }
    }

    #[Test]
    public function getQueryResultClonesTheGivenPrototype(): void
    {
        $result = $this->createDriver(true)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);
        static::assertInstanceOf(Result::class, $result);

        $prototype = new ResultSet();
        $resultSet = $result->getQueryResult($prototype);

        static::assertNotSame($prototype, $resultSet);
        static::assertInstanceOf(ResultSet::class, $resultSet);
    }

    #[Test]
    public function getQueryResultOnNonQueryResultThrows(): void
    {
        $result = $this->executeNonQuery();
        static::assertInstanceOf(Result::class, $result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot produce a query result set from a result that is not a query result',
        );

        $result->getQueryResult();
    }

    #[Test]
    public function getQueryResultSeedsResultSetFromQueryResult(): void
    {
        $result = $this->createDriver(true)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);
        static::assertInstanceOf(Result::class, $result);

        $resultSet = $result->getQueryResult();

        static::assertSame(1, $resultSet->count());
    }

    #[Test]
    public function initializeWithStatementDefaultsBufferedStateToUnknown(): void
    {
        $mysqli = $this->createMysqli();
        $stmt   = $mysqli->prepare('SELECT * FROM test');
        static::assertInstanceOf(mysqli_stmt::class, $stmt);
        $stmt->execute();

        $result = new Result();
        $result->initialize($stmt, null);

        static::assertNull($result->isBuffered());
    }

    #[Test]
    public function insertReturnsGeneratedValueAndAffectedRows(): void
    {
        $driver = $this->createDriver(false);
        $result = $driver->createStatement('INSERT INTO test (name, value) VALUES (?, ?)')
            ->execute($this->createParameterContainer(['new', 'val']));

        static::assertNotNull($result);
        static::assertSame(1, $result->getAffectedRows());
        static::assertSame($driver->getLastGeneratedValue(), $result->getGeneratedValue());
        static::assertIsInt($driver->getLastGeneratedValue());

        $this->createDriver(false)
            ->createStatement('DELETE FROM test WHERE name = ?')
            ->execute($this->createParameterContainer(['new']));
    }

    #[Test]
    public function nextBeforeAnyFetchAdvancesPosition(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);

        $result->next();

        static::assertSame(1, $result->key());
    }

    #[Test]
    public function prepareInvalidSqlThrowsInvalidQueryException(): void
    {
        $statement = $this->createDriver(false)->createStatement('SELECT FROM WHERE');

        mysqli_report(MYSQLI_REPORT_OFF);
        try {
            $this->expectException(InvalidQueryException::class);
            $this->expectExceptionMessage("Statement couldn't be produced");
            $statement->prepare();
        } finally {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }
    }

    #[Test]
    public function prepareTwiceThrows(): void
    {
        $statement = $this->createDriver(false)->createStatement('SELECT 1');
        $statement->prepare();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This statement has already been prepared');
        $statement->prepare();
    }

    #[Test]
    public function rewindOnNonQueryResultThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot rewind a result that is not a query result');

        $this->executeNonQuery()->rewind();
    }

    #[Test]
    public function rewindUnbufferedAfterIterationThrows(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);
        $result->current();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unbuffered results cannot be rewound for multiple iterations');
        $result->rewind();
    }

    #[Test]
    public function statementContainerAccessors(): void
    {
        $driver    = $this->createDriver(false);
        $statement = $driver->createStatement('SELECT 1');

        static::assertInstanceOf(ParameterContainer::class, $statement->getParameterContainer());

        $container = new ParameterContainer();
        static::assertSame($statement, $statement->setParameterContainer($container));
        static::assertSame($container, $statement->getParameterContainer());

        static::assertSame($statement, $statement->setSql('SELECT 2'));
        static::assertSame('SELECT 2', $statement->getSql());
    }

    #[Test]
    public function statementResultWithoutMetadataYieldsNoRows(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('UPDATE test SET value = value WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);
        static::assertNull($result->current());
    }

    #[Test]
    public function unbufferedResultClosesStatementAfterFullIteration(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE value = ?')
            ->execute($this->createParameterContainer(['bar']));

        static::assertNotNull($result);
        static::assertCount(3, iterator_to_array($result, preserve_keys: false));
    }

    #[Test]
    public function unbufferedResultCountThrows(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Row count is not available in unbuffered result sets.');
        $result->count();
    }

    #[Test]
    public function unbufferedStatementResultIterates(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);
        static::assertFalse($result->isBuffered());
        static::assertSame(['id' => 1, 'name' => 'foo', 'value' => 'bar'], $result->current());
    }

    #[Test]
    public function updateReturnsAffectedRows(): void
    {
        $driver = $this->createDriver(false);
        $result = $driver->createStatement('UPDATE test SET value = ? WHERE id = ?')
            ->execute($this->createParameterContainer(['updated', 1]));

        static::assertNotNull($result);
        static::assertSame(1, $result->getAffectedRows());

        $driver->createStatement('UPDATE test SET value = ? WHERE id = ?')
            ->execute($this->createParameterContainer(['bar', 1]));
    }

    #[Test]
    public function validReturnsTrueAfterCurrent(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT * FROM test WHERE id = ?')
            ->execute($this->createParameterContainer([1]));

        static::assertNotNull($result);
        $result->current();

        static::assertTrue($result->valid());
    }

    /**
     * @return array{hostname: string, username: string, password: string, database: string, port: int}
     */
    private function connectionParameters(): array
    {
        $host = (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME');
        if ('' === $host) {
            $host = 'localhost';
        }

        $port = (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PORT');
        $port = '' === $port ? 3306 : (int) $port;

        return [
            'hostname' => $host,
            'username' => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
            'password' => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
            'database' => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
            'port'     => $port,
        ];
    }

    private function createDriver(bool $bufferResults = false): Driver
    {
        return new Driver(
            new Connection($this->createMysqli()),
            new Statement(bufferResults: $bufferResults),
            new Result(),
        );
    }

    private function createMysqli(): mysqli
    {
        $parameters = $this->connectionParameters();

        return new mysqli(
            $parameters['hostname'],
            $parameters['username'],
            $parameters['password'],
            $parameters['database'],
            $parameters['port'],
        );
    }

    /**
     * @param list<mixed> $values
     */
    private function createParameterContainer(array $values): ParameterContainer
    {
        $container = new ParameterContainer();
        foreach ($values as $key => $value) {
            $container->offsetSet(
                $key,
                $value,
                is_int($value) ? ParameterContainer::TYPE_INTEGER : ParameterContainer::TYPE_STRING,
            );
        }

        return $container;
    }

    private function executeNonQuery(): ResultInterface
    {
        $mysqli     = $this->createMysqli();
        $connection = new Connection($mysqli);
        new Driver($connection, new Statement(), new Result());

        $result = $connection->execute('UPDATE test SET name = name WHERE id = 1');
        static::assertNotNull($result);

        return $result;
    }
}
