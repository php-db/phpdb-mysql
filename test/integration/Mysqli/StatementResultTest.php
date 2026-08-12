<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Mysqli;

use mysqli;
use mysqli_result;
use mysqli_stmt;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Result;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function getenv;
use function is_int;
use function iterator_to_array;

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
    public function connectionExecuteUsesMysqliResult(): void
    {
        $mysqli     = $this->createMysqli();
        $connection = new Connection($mysqli);
        new Driver($connection, new Statement(), new Result());

        $result = $connection->execute('SELECT * FROM test');
        static::assertNotNull($result);

        static::assertTrue($result->isBuffered());
        static::assertTrue($result->isQueryResult());
        static::assertInstanceOf(mysqli_result::class, $result->getResource());
        static::assertSame(3, $result->getFieldCount());
        static::assertSame(4, $result->count());
        static::assertSame(4, $result->getAffectedRows());

        static::assertCount(4, iterator_to_array($result));

        $result->rewind();
        static::assertSame(['id' => '1', 'name' => 'foo', 'value' => 'bar'], $result->current());
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
    public function executeWithEmptyArray(): void
    {
        $result = $this->createDriver(false)
            ->createStatement('SELECT 1')
            ->execute([]);

        static::assertNotNull($result);
        static::assertTrue($result->isQueryResult());
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
        $result = $this->createDriver(false)
            ->createStatement('UPDATE test SET value = ? WHERE id = ?')
            ->execute($this->createParameterContainer(['updated', 1]));

        static::assertNotNull($result);
        static::assertSame(1, $result->getAffectedRows());
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
        $host = (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME');
        if ('' === $host) {
            $host = 'localhost';
        }

        $port = (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PORT');
        $port = '' === $port ? 3306 : (int) $port;

        return new mysqli(
            $host,
            (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
            (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
            (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
            $port,
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
}
