<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Mysql\Container\PdoStatementFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('container')]
#[Group('integration')]
#[CoversClass(PdoStatementFactory::class)]
#[CoversMethod(PdoStatementFactory::class, '__invoke')]
final class PdoStatementFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    #[Test]
    public function invokeReturnsPdoStatement(): void
    {
        $factory   = new PdoStatementFactory();
        $statement = $factory(
            $this->container,
            StatementInterface::class,
            $this->config[AdapterInterface::class],
        );
        static::assertInstanceOf(StatementInterface::class, $statement);
        static::assertInstanceOf(Statement::class, $statement);
    }
}
