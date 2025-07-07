<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Container;

use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Mysql\Container\MysqliStatementFactory;
use PhpDb\Adapter\Mysql\Driver\Mysqli\Statement;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(MysqliStatementFactory::class)]
#[Attributes\CoversMethod(MysqliStatementFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class MysqliStatementFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsMysqliStatement(): void
    {
        $this->getAdapter([
            'db' => [
                'driver'  => 'Mysqli',
                'options' => [
                    'buffer_results' => false,
                ],
            ],
        ]);

        $factory   = new MysqliStatementFactory();
        $statement = $factory($this->container);

        self::assertInstanceOf(StatementInterface::class, $statement);
        self::assertInstanceOf(Statement::class, $statement);
    }
}
