<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Pdo;

use Override;
use PDOStatement;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Mysql\Pdo\Driver;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function execute(): void
    {
        $mockPdoStatement = $this->createMock(PDOStatement::class);
        $pdo              = new TestAsset\CtorlessPdo($mockPdoStatement);
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');

        $result = $this->statement->execute();
        static::assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * @todo Implement testGetParameterContainer().
     */
    #[Test]
    public function getParameterContainer(): void
    {
        $container = new ParameterContainer();
        $this->statement->setParameterContainer($container);
        static::assertSame($container, $this->statement->getParameterContainer());
    }

    #[Test]
    public function getResource(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $this->statement->setResource($stmt);

        static::assertSame($stmt, $this->statement->getResource());
    }

    #[Test]
    public function getSql(): void
    {
        $this->statement->setSql('SELECT 1');
        static::assertSame('SELECT 1', $this->statement->getSql());
    }

    #[Test]
    public function isPrepared(): void
    {
        static::assertFalse($this->statement->isPrepared());

        $mockPdoStatement = $this->createMock(PDOStatement::class);
        $pdo              = new TestAsset\CtorlessPdo($mockPdoStatement);
        $this->statement->initialize($pdo);
        $this->statement->prepare('SELECT 1');

        static::assertTrue($this->statement->isPrepared());
    }

    #[Test]
    public function prepare(): void
    {
        $mockPdoStatement = $this->createMock(PDOStatement::class);
        $pdo              = new TestAsset\CtorlessPdo($mockPdoStatement);
        $this->statement->initialize($pdo);

        $result = $this->statement->prepare('SELECT 1');
        static::assertInstanceOf(Statement::class, $result);
    }

    #[Test]
    public function setDriver(): void
    {
        static::assertInstanceOf(PdoDriverInterface::class, $this->pdo);
        static::assertEquals($this->statement, $this->statement->setDriver($this->pdo));
    }

    #[Test]
    public function setParameterContainer(): void
    {
        static::assertSame($this->statement, $this->statement->setParameterContainer(new ParameterContainer()));
    }

    #[Test]
    public function setSql(): void
    {
        $this->statement->setSql('SELECT 1');
        static::assertSame('SELECT 1', $this->statement->getSql());
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->statement = new Statement();
        $this->pdo       = new Driver(
            $this->createMock(AbstractPdoConnection::class),
            $this->statement,
            new Result(),
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void {}
}
