<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Container;

use PhpDb\Adapter\Mysql\Container\MysqlMetadataFactory;
use PhpDb\Adapter\Mysql\Metadata\Source\MysqlMetadata;
use PhpDb\Metadata\MetadataInterface;
use PhpDbIntegrationTest\Adapter\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(MysqlMetadataFactory::class)]
#[CoversMethod(MysqlMetadataFactory::class, '__invoke')]
final class MysqlMetadataFactoryTest extends TestCase
{
    use SetupTrait;

    public function testFactoryReturnsMysqlMetadata(): void
    {
        $factory  = new MysqlMetadataFactory();
        $metadata = $factory($this->container);
        self::assertInstanceOf(MetadataInterface::class, $metadata);
        self::assertInstanceOf(MysqlMetadata::class, $metadata);
    }
}
