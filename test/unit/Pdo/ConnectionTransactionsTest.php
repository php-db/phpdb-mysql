<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Override;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\Pdo\Connection;
use PhpDbTest\Mysql\Pdo\TestAsset\PdoStubDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Tests for {@see \PhpDb\Adapter\Mysql\Driver\Pdo\Connection} transaction support
 */
#[CoversClass(Connection::class)]
final class ConnectionTransactionsTest extends TestCase
{
    protected Connection $wrapper;

    #[Test]
    public function beginTransactionReturnsInstanceOfConnection(): void
    {
        static::assertInstanceOf(Connection::class, $this->wrapper->beginTransaction());
    }

    #[Test]
    public function beginTransactionSetsInTransactionAtTrue(): void
    {
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
    }

    #[Test]
    public function commitReturnsInstanceOfConnection(): void
    {
        $this->wrapper->beginTransaction();
        static::assertInstanceOf(Connection::class, $this->wrapper->commit());
    }

    #[Test]
    public function commitSetsInTransactionAtFalse(): void
    {
        $this->wrapper->beginTransaction();
        $this->wrapper->commit();
        static::assertFalse($this->wrapper->inTransaction());
    }

    /**
     * Standalone commit after a SET autocommit=0;
     */
    #[Test]
    public function commitWithoutBeginReturnsInstanceOfConnection(): void
    {
        static::assertInstanceOf(Connection::class, $this->wrapper->commit());
    }

    #[Test]
    public function nestedTransactionsCommit(): void
    {
        static::assertFalse($this->wrapper->inTransaction());

        // 1st transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(1, $this->getNestedTransactionsCount($this->wrapper));

        // 2nd transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(2, $this->getNestedTransactionsCount($this->wrapper));

        // 1st commit
        $this->wrapper->commit();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(1, $this->getNestedTransactionsCount($this->wrapper));

        // 2nd commit
        $this->wrapper->commit();
        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->getNestedTransactionsCount($this->wrapper));
    }

    #[Test]
    public function nestedTransactionsRollback(): void
    {
        static::assertFalse($this->wrapper->inTransaction());

        // 1st transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(1, $this->getNestedTransactionsCount($this->wrapper));

        // 2nd transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(2, $this->getNestedTransactionsCount($this->wrapper));

        // Rollback
        $this->wrapper->rollback();
        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->getNestedTransactionsCount($this->wrapper));
    }

    #[Test]
    public function rollbackDisconnectedThrowsException(): void
    {
        $this->wrapper->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Must be connected before you can rollback');
        $this->wrapper->rollback();
    }

    #[Test]
    public function rollbackReturnsInstanceOfConnection(): void
    {
        $this->wrapper->beginTransaction();
        static::assertInstanceOf(Connection::class, $this->wrapper->rollback());
    }

    #[Test]
    public function rollbackSetsInTransactionAtFalse(): void
    {
        $this->wrapper->beginTransaction();
        $this->wrapper->rollback();
        static::assertFalse($this->wrapper->inTransaction());
    }

    #[Test]
    public function rollbackWithoutBeginThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Must call beginTransaction() before you can rollback');
        $this->wrapper->rollback();
    }

    /**
     * Standalone commit after a SET autocommit=0;
     */
    #[Test]
    public function standaloneCommit(): void
    {
        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->getNestedTransactionsCount($this->wrapper));

        $this->wrapper->commit();

        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->getNestedTransactionsCount($this->wrapper));
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function setUp(): void
    {
        $this->wrapper = new Connection([]);
        // bypass setResource(), which calls PDO::getAttribute() and would fail
        // against the stub's uninitialized internal PDO state
        (new ReflectionProperty($this->wrapper, 'resource'))->setValue(
            $this->wrapper,
            new PdoStubDriver('foo', 'bar', 'baz'),
        );
    }

    private function getNestedTransactionsCount(Connection $connection): int
    {
        return (new ReflectionProperty($connection, 'nestedTransactionsCount'))->getValue($connection);
    }
}
