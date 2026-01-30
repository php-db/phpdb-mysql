<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Exception\InvalidArgumentException;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Statement::class)]
final class StatementTest extends TestCase
{
    private ParameterContainer $parameterContainer;
    private Statement $statement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterContainer = new ParameterContainer([]);
        $this->statement          = new Statement($this->parameterContainer);
    }

    public function testSetDriverWithInvalidDriverThrowsException(): void
    {
        $driver = $this->createStub(DriverInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver must be an instance of ' . Driver::class);
        $this->statement->setDriver($driver);
    }
}
