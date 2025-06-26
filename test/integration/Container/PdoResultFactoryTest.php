<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Mysql\Container\PdoResultFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('container')]
#[Group('integration')]
#[CoversClass(PdoResultFactory::class)]
#[CoversMethod(PdoResultFactory::class, '__invoke')]
final class PdoResultFactoryTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testInvokeReturnsPdoResult(): void
    {
        $factory = new PdoResultFactory();
        $result  = $factory($this->container);

        self::assertInstanceOf(ResultInterface::class, $result);
        self::assertInstanceOf(Result::class, $result);
    }
}
