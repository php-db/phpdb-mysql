<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\Pdo\Statement as PdoStatement;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Mysql\Container\StatementInterfaceFactory;
use PhpDb\Adapter\Mysql\Driver\Mysqli\Statement as MysqliStatement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('container')]
#[Group('integration')]
#[CoversClass(StatementInterfaceFactory::class)]
#[CoversMethod(StatementInterfaceFactory::class, '__invoke')]
final class StatementInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsPdoStatement(): void
    {
        $factory   = new StatementInterfaceFactory();
        $statement = $factory($this->container, PdoStatement::class);
        self::assertInstanceOf(StatementInterface::class, $statement);
        self::assertInstanceOf(PdoStatement::class, $statement);
    }

    public function testInvokeReturnsMysqliStatement(): void
    {
        $factory   = new StatementInterfaceFactory();
        $statement = $factory($this->container, MysqliStatement::class);
        self::assertInstanceOf(StatementInterface::class, $statement);
        self::assertInstanceOf(MysqliStatement::class, $statement);
    }
}
