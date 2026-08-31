<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Mysql\Container\StatementInterfaceFactory;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(StatementInterfaceFactory::class)]
#[Attributes\CoversMethod(StatementInterfaceFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class StatementInterfaceFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    #[Test]
    public function invokeReturnsMysqliStatement(): void
    {
        $this->getAdapter([
            AdapterInterface::class => [
                'driver'  => 'Mysqli',
                'options' => [
                    'buffer_results' => false,
                ],
            ],
        ]);

        $factory   = new StatementInterfaceFactory();
        $statement = $factory(
            $this->container,
            StatementInterface::class,
            $this->config[AdapterInterface::class],
        );

        static::assertInstanceOf(StatementInterface::class, $statement);
        static::assertInstanceOf(Statement::class, $statement);
    }
}
