<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\Statement as PdoStatement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Mysql\ConfigProvider;
use PhpDb\Mysql\Connection;
use PhpDb\Mysql\Container;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Metadata\Source;
use PhpDb\Mysql\Pdo;
use PhpDb\Mysql\Statement;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;

#[CoversMethod(ConfigProvider::class, 'getDependencies')]
#[CoversMethod(ConfigProvider::class, '__invoke')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function getDependenciesProvidesAliasesAndFactories(): void
    {
        $dependencies = (new ConfigProvider())->getDependencies();

        static::assertSame(['aliases', 'factories'], array_keys($dependencies));

        $aliases = $dependencies['aliases'];
        static::assertSame(Driver::class, $aliases['Mysqli']);
        static::assertSame(Pdo\Driver::class, $aliases['PDO_MySQL']);
        static::assertSame(Driver::class, $aliases[DriverInterface::class]);
        static::assertSame(Pdo\Driver::class, $aliases[PdoDriverInterface::class]);
        static::assertSame(Source::class, $aliases[MetadataInterface::class]);

        $factories = $dependencies['factories'];
        static::assertSame(Container\DriverInterfaceFactory::class, $factories[Driver::class]);
        static::assertSame(Container\ConnectionInterfaceFactory::class, $factories[Connection::class]);
        static::assertSame(Container\StatementInterfaceFactory::class, $factories[Statement::class]);
        static::assertSame(Container\PdoDriverInterfaceFactory::class, $factories[Pdo\Driver::class]);
        static::assertSame(Container\PdoConnectionInterfaceFactory::class, $factories[Pdo\Connection::class]);
        static::assertSame(Container\MetadataInterfaceFactory::class, $factories[Source::class]);
        static::assertSame(Container\PdoStatementFactory::class, $factories[PdoStatement::class]);
        static::assertSame(Container\PlatformInterfaceFactory::class, $factories[PlatformInterface::class]);
    }

    #[Test]
    public function invokeWrapsDependencies(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(['dependencies' => $provider->getDependencies()], $provider());
    }
}
