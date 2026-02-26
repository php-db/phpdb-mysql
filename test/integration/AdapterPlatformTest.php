<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Platform;

use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Driver;
use PhpDb\Mysql\Pdo\Driver as PdoDriver;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[CoversMethod(AdapterPlatform::class, 'quoteValue')]
final class AdapterPlatformTest extends TestCase
{
    use SetupTrait;

    /** @return array<string, array{string, string}> */
    public static function quoteValueProvider(): array
    {
        return [
            'plain string' => ['value', '\'value\''],
            'single quote' => ["it's", "'it\\'s'"],
            'backslash'    => ['val\\ue', "'val\\\\ue'"],
            'empty string' => ['', "''"],
            'double quote' => ['say "hello"', "'say \\\"hello\\\"'"],
            'null byte'    => ["val\x00ue", "'val\\0ue'"],
        ];
    }

    #[DataProvider('quoteValueProvider')]
    public function testQuoteValueWithMysqli(string $input, string $expected): void
    {
        $this->driver = Driver::class;
        $adapter      = $this->getAdapter();

        $platform = new AdapterPlatform($adapter->getDriver());
        $value    = $platform->quoteValue($input);
        self::assertSame($expected, $value);
    }

    #[DataProvider('quoteValueProvider')]
    public function testQuoteValueWithPdoMysql(string $input, string $expected): void
    {
        $this->driver = PdoDriver::class;
        $adapter      = $this->getAdapter();

        $platform = new AdapterPlatform($adapter->getDriver());
        $value    = $platform->quoteValue($input);
        self::assertSame($expected, $value);
    }
}
