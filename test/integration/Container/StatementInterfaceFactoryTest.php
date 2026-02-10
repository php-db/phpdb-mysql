<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Mysql\Container\StatementInterfaceFactory;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(StatementInterfaceFactory::class)]
#[Attributes\CoversMethod(StatementInterfaceFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class StatementInterfaceFactoryTest extends TestCase
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

        $factory   = new StatementInterfaceFactory();
        $statement = $factory($this->container);

        self::assertInstanceOf(StatementInterface::class, $statement);
        self::assertInstanceOf(Statement::class, $statement);
    }
}
