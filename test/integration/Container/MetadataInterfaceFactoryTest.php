<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Container;

use PhpDb\Metadata\MetadataInterface;
use PhpDb\Mysql\Container\MetadataInterfaceFactory;
use PhpDb\Mysql\Metadata\Source;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetadataInterfaceFactory::class)]
#[CoversMethod(MetadataInterfaceFactory::class, '__invoke')]
final class MetadataInterfaceFactoryTest extends TestCase
{
    use SetupTrait;

    #[Test]
    public function factoryReturnsMysqlMetadata(): void
    {
        $factory  = new MetadataInterfaceFactory();
        $metadata = $factory($this->container, MetadataInterface::class);
        static::assertInstanceOf(MetadataInterface::class, $metadata);
        static::assertInstanceOf(Source::class, $metadata);
    }
}
