<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Platform;

use mysqli;
use Override;
use PDO;
use PhpDb\Adapter\Driver\Pdo\AbstractPdoConnection;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\Mysql\Sql\Platform;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversMethod(AdapterPlatform::class, '__construct')]
#[CoversMethod(AdapterPlatform::class, 'getName')]
#[CoversMethod(AdapterPlatform::class, 'getStatementFactory')]
#[CoversMethod(AdapterPlatform::class, 'quoteIdentifierChain')]
#[CoversMethod(AdapterPlatform::class, 'quoteValue')]
#[CoversMethod(AdapterPlatform::class, 'quoteTrustedValue')]
#[CoversMethod(AdapterPlatform::class, 'quoteViaDriver')]
final class AdapterPlatformTest extends TestCase
{
    protected AdapterPlatform $platform;

    #[Test]
    public function getIdentifierSeparator(): void
    {
        static::assertSame('.', $this->platform->getIdentifierSeparator());
    }

    #[Test]
    public function getName(): void
    {
        static::assertSame('MySQL', $this->platform->getName());
    }

    #[Test]
    public function getQuoteIdentifierSymbol(): void
    {
        static::assertSame('`', $this->platform->getQuoteIdentifierSymbol());
    }

    #[Test]
    public function getQuoteValueSymbol(): void
    {
        static::assertSame("'", $this->platform->getQuoteValueSymbol());
    }

    #[Test]
    public function getStatementFactory(): void
    {
        static::assertInstanceOf(Platform::class, $this->platform->getStatementFactory());
    }

    #[Test]
    public function quoteIdentifier(): void
    {
        static::assertSame('`identifier`', $this->platform->quoteIdentifier('identifier'));
        static::assertSame('`ident``ifier`', $this->platform->quoteIdentifier('ident`ifier'));
        static::assertSame('`namespace:$identifier`', $this->platform->quoteIdentifier('namespace:$identifier'));
    }

    #[Test]
    public function quoteIdentifierChain(): void
    {
        static::assertSame('`identifier`', $this->platform->quoteIdentifierChain('identifier'));
        static::assertSame('`identifier`', $this->platform->quoteIdentifierChain(['identifier']));
        static::assertSame('`schema`.`identifier`', $this->platform->quoteIdentifierChain(['schema', 'identifier']));

        static::assertSame('`ident``ifier`', $this->platform->quoteIdentifierChain('ident`ifier'));
        static::assertSame('`ident``ifier`', $this->platform->quoteIdentifierChain(['ident`ifier']));
        static::assertSame(
            '`schema`.`ident``ifier`',
            $this->platform->quoteIdentifierChain(['schema', 'ident`ifier']),
        );
    }

    #[Test]
    public function quoteIdentifierInFragment(): void
    {
        static::assertSame('`foo`.`bar`', $this->platform->quoteIdentifierInFragment('foo.bar'));
        static::assertSame('`foo` as `bar`', $this->platform->quoteIdentifierInFragment('foo as bar'));
        static::assertSame('`$TableName`.`bar`', $this->platform->quoteIdentifierInFragment('$TableName.bar'));
        static::assertSame(
            '`cmis:$TableName` as `cmis:TableAlias`',
            $this->platform->quoteIdentifierInFragment('cmis:$TableName as cmis:TableAlias'),
        );

        static::assertSame(
            '`foo-bar`.`bar-foo`',
            $this->platform->quoteIdentifierInFragment('foo-bar.bar-foo'),
        );
        static::assertSame(
            '`foo-bar` as `bar-foo`',
            $this->platform->quoteIdentifierInFragment('foo-bar as bar-foo'),
        );
        static::assertSame(
            '`$TableName-$ColumnName`.`bar-foo`',
            $this->platform->quoteIdentifierInFragment('$TableName-$ColumnName.bar-foo'),
        );
        static::assertSame(
            '`cmis:$TableName-$ColumnName` as `cmis:TableAlias-ColumnAlias`',
            $this->platform->quoteIdentifierInFragment('cmis:$TableName-$ColumnName as cmis:TableAlias-ColumnAlias'),
        );

        // single char words
        static::assertSame(
            '(`foo`.`bar` = `boo`.`baz`)',
            $this->platform->quoteIdentifierInFragment('(foo.bar = boo.baz)', ['(', ')', '=']),
        );
        static::assertSame(
            '(`foo`.`bar`=`boo`.`baz`)',
            $this->platform->quoteIdentifierInFragment('(foo.bar=boo.baz)', ['(', ')', '=']),
        );
        static::assertSame('`foo`=`bar`', $this->platform->quoteIdentifierInFragment('foo=bar', ['=']));

        static::assertSame(
            '(`foo-bar`.`bar-foo` = `boo-baz`.`baz-boo`)',
            $this->platform->quoteIdentifierInFragment('(foo-bar.bar-foo = boo-baz.baz-boo)', ['(', ')', '=']),
        );
        static::assertSame(
            '(`foo-bar`.`bar-foo`=`boo-baz`.`baz-boo`)',
            $this->platform->quoteIdentifierInFragment('(foo-bar.bar-foo=boo-baz.baz-boo)', ['(', ')', '=']),
        );
        static::assertSame(
            '`foo-bar`=`bar-foo`',
            $this->platform->quoteIdentifierInFragment('foo-bar=bar-foo', ['=']),
        );

        // case insensitive safe words
        static::assertSame(
            '(`foo`.`bar` = `boo`.`baz`) AND (`foo`.`baz` = `boo`.`baz`)',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and'],
            ),
        );

        static::assertSame(
            '(`foo-bar`.`bar-foo` = `boo-baz`.`baz-boo`) AND (`foo-baz`.`baz-foo` = `boo-baz`.`baz-boo`)',
            $this->platform->quoteIdentifierInFragment(
                '(foo-bar.bar-foo = boo-baz.baz-boo) AND (foo-baz.baz-foo = boo-baz.baz-boo)',
                ['(', ')', '=', 'and'],
            ),
        );

        // case insensitive safe words in field
        static::assertSame(
            '(`foo`.`bar` = `boo`.baz) AND (`foo`.baz = `boo`.baz)',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and', 'bAz'],
            ),
        );

        // case insensitive safe words in field
        static::assertSame(
            '(`foo-bar`.`bar-foo` = `boo-baz`.baz-boo) AND (`foo-baz`.`baz-foo` = `boo-baz`.baz-boo)',
            $this->platform->quoteIdentifierInFragment(
                '(foo-bar.bar-foo = boo-baz.baz-boo) AND (foo-baz.baz-foo = boo-baz.baz-boo)',
                ['(', ')', '=', 'and', 'bAz-BOo'],
            ),
        );
    }

    #[Test]
    public function quoteTrustedValue(): void
    {
        static::assertSame("'value'", $this->platform->quoteTrustedValue('value'));
        static::assertSame("'Foo O\\'Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        static::assertSame(
            '\'\\\'; DELETE FROM some_table; -- \'',
            $this->platform->quoteTrustedValue('\'; DELETE FROM some_table; -- '),
        );

        //                   '\\\'; DELETE FROM some_table; -- '  <- actual below
        static::assertSame(
            "'\\\\\\'; DELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue('\\\'; DELETE FROM some_table; -- '),
        );
    }

    #[Test]
    public function quoteValueListThrowsWhenTheDriverCannotQuote(): void
    {
        $this->expectException(RuntimeException::class);

        $this->platform->quoteValueList("Foo O'Bar");
    }

    #[Test]
    public function quoteValueThrowsWhenTheDriverCannotQuote(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            RuntimeException::VULNERABLE_PLATFORM_QUOTE,
            AdapterPlatform::class,
            AdapterPlatform::class . '::quoteValue',
        ));

        $this->platform->quoteValue("Foo O'Bar");
    }

    #[Test]
    public function quoteViaDriverWithMysqli(): void
    {
        $mysqli = $this->createMock(mysqli::class);
        $mysqli->expects($this->once())
            ->method('real_escape_string')
            ->with("a'b")
            ->willReturn("a\\'b");

        $platform = new AdapterPlatform($mysqli);

        static::assertSame("'a\\'b'", $platform->quoteValue("a'b"));
    }

    #[Test]
    public function quoteViaDriverWithPdo(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('quote')
            ->with("a'b")
            ->willReturn("'a''b'");

        $platform = new AdapterPlatform($pdo);

        static::assertSame("'a''b'", $platform->quoteValue("a'b"));
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    #[Override]
    protected function setUp(): void
    {
        $pdo = new Driver(
            $this->createStub(AbstractPdoConnection::class),
            $this->createStub(Statement::class),
            $this->createStub(Result::class),
        );
        $this->platform = new AdapterPlatform($pdo);
    }
}
