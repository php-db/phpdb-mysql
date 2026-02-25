<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Mysql\Container\MysqliResultFactory;
use PhpDb\Mysql\Result;
use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

#[Attributes\CoversClass(MysqliResultFactory::class)]
#[Attributes\CoversMethod(MysqliResultFactory::class, '__invoke')]
#[Attributes\Group('container')]
#[Attributes\Group('integration')]
#[Attributes\Group('integration-mysqli')]
final class MysqliResultFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsMysqliResult(): void
    {
        $factory = new MysqliResultFactory();
        $result  = $factory($this->container);

        self::assertInstanceOf(ResultInterface::class, $result);
        self::assertInstanceOf(Result::class, $result);
    }
}
