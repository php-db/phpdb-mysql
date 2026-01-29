<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Mysql\Container\PdoStatementFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('container')]
#[Group('integration')]
#[CoversClass(PdoStatementFactory::class)]
#[CoversMethod(PdoStatementFactory::class, '__invoke')]
final class PdoStatementFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsPdoStatement(): void
    {
        $factory   = new PdoStatementFactory();
        $statement = $factory($this->container);
        self::assertInstanceOf(StatementInterface::class, $statement);
        self::assertInstanceOf(Statement::class, $statement);
    }
}
