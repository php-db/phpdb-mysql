<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Mysql\Container\MysqliResultFactory;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Result;
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
