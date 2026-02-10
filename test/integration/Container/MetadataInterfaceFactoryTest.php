<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Metadata\MetadataInterface;
use PhpDb\Mysql\Container\MetadataInterfaceFactory;
use PhpDb\Mysql\Metadata\Source;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetadataInterfaceFactory::class)]
#[CoversMethod(MetadataInterfaceFactory::class, '__invoke')]
final class MetadataInterfaceFactoryTest extends TestCase
{
    use SetupTrait;

    public function testFactoryReturnsMysqlMetadata(): void
    {
        $factory  = new MetadataInterfaceFactory();
        $metadata = $factory($this->container, MetadataInterface::class);
        self::assertInstanceOf(MetadataInterface::class, $metadata);
        self::assertInstanceOf(Source::class, $metadata);
    }
}
