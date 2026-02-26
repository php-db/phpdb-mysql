<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Statement::class, 'setDriver')]
#[CoversMethod(Statement::class, 'setParameterContainer')]
#[CoversMethod(Statement::class, 'getParameterContainer')]
#[CoversMethod(Statement::class, 'getResource')]
#[CoversMethod(Statement::class, 'setSql')]
#[CoversMethod(Statement::class, 'getSql')]
#[CoversMethod(Statement::class, 'prepare')]
#[CoversMethod(Statement::class, 'isPrepared')]
#[CoversMethod(Statement::class, 'execute')]
final class StatementTest extends TestCase
{
    protected ?Driver $pdo;
    protected Statement $statement;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->statement = new Statement();
        $this->pdo       = new Driver(
            $this->createMock(Connection::class),
            $this->statement,
            new Result(),
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testSetDriver(): void
    {
        self::assertInstanceOf(PdoDriverInterface::class, $this->pdo);
        self::assertEquals($this->statement, $this->statement->setDriver($this->pdo));
    }

    public function testSetParameterContainer(): void
    {
        self::assertSame($this->statement, $this->statement->setParameterContainer(new ParameterContainer()));
    }

    /**
     * @todo Implement testGetParameterContainer().
     */
    public function testGetParameterContainer(): void
    {
        $container = new ParameterContainer();
        $this->statement->setParameterContainer($container);
        self::assertSame($container, $this->statement->getParameterContainer());
    }

    public function testGetResource(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $this->statement->setResource($stmt);

        self::assertSame($stmt, $this->statement->getResource());
    }

    public function testSetSql(): void
    {
        $this->statement->setSql('SELECT 1');
        self::assertEquals('SELECT 1', $this->statement->getSql());
    }

    public function testGetSql(): void
    {
        $this->statement->setSql('SELECT 1');
        self::assertEquals('SELECT 1', $this->statement->getSql());
    }

    public function testPrepare(): void
    {
        $mockPdoStatement = $this->createMock(PDOStatement::class);
        $pdo              = new TestAsset\CtorlessPdo($mockPdoStatement);
        $this->statement->initialize($pdo);

        $result = $this->statement->prepare('SELECT 1');
        self::assertInstanceOf(Statement::class, $result);
    }

    public function testIsPrepared(): void
    {
        self::assertFalse($this->statement->isPrepared());

        $mockPdoStatement = $this->createMock(PDOStatement::class);
        $pdo              = new TestAsset\CtorlessPdo($mockPdoStatement);
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');

        self::assertTrue($this->statement->isPrepared());
    }

    public function testExecute(): void
    {
        $mockPdoStatement = $this->createMock(PDOStatement::class);
        $pdo              = new TestAsset\CtorlessPdo($mockPdoStatement);
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');

        $result = $this->statement->execute();
        self::assertInstanceOf(ResultInterface::class, $result);
    }
}
