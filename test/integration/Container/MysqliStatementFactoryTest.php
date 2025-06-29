<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Mysql\Container\MysqliStatementFactory;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Statement;
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
