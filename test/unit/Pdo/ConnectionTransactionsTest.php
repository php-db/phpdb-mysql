<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Override;
use PhpDb\Adapter\Driver\AbstractConnection;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\Pdo\Connection;
use PhpDbTest\Mysql\Pdo\TestAsset\ConnectionWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see \PhpDb\Adapter\Mysql\Driver\Pdo\Connection} transaction support
 */
#[CoversClass(Connection::class)]
#[CoversClass(AbstractConnection::class)]
#[CoversMethod(Connection::class, 'beginTransaction')]
#[CoversMethod(Connection::class, 'inTransaction')]
#[CoversMethod(Connection::class, 'commit')]
#[CoversMethod(Connection::class, 'rollback')]
final class ConnectionTransactionsTest extends TestCase
{
    protected ConnectionWrapper $wrapper;

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
        $nested = 0;

        static::assertFalse($this->wrapper->inTransaction());

        // 1st transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(++$nested, $this->wrapper->getNestedTransactionsCount());

        // 2nd transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(++$nested, $this->wrapper->getNestedTransactionsCount());

        // 1st commit
        $this->wrapper->commit();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(--$nested, $this->wrapper->getNestedTransactionsCount());

        // 2nd commit
        $this->wrapper->commit();
        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(--$nested, $this->wrapper->getNestedTransactionsCount());
    }

    #[Test]
    public function nestedTransactionsRollback(): void
    {
        $nested = 0;

        static::assertFalse($this->wrapper->inTransaction());

        // 1st transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(++$nested, $this->wrapper->getNestedTransactionsCount());

        // 2nd transaction
        $this->wrapper->beginTransaction();
        static::assertTrue($this->wrapper->inTransaction());
        static::assertSame(++$nested, $this->wrapper->getNestedTransactionsCount());

        // Rollback
        $this->wrapper->rollback();
        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->wrapper->getNestedTransactionsCount());
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
        static::assertSame(0, $this->wrapper->getNestedTransactionsCount());

        $this->wrapper->commit();

        static::assertFalse($this->wrapper->inTransaction());
        static::assertSame(0, $this->wrapper->getNestedTransactionsCount());
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function setUp(): void
    {
        $this->wrapper = new ConnectionWrapper();
    }
}
