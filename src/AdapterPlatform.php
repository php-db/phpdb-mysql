<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use mysqli;
use Override;
use PDO;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Platform\AbstractPlatform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function implode;
use function str_replace;

class AdapterPlatform extends AbstractPlatform
{
    final public const PLATFORM_NAME = 'MySQL';

    /**
     * {@inheritDoc}
     */
    protected array $quoteIdentifier = ['`', '`'];

    /**
     * {@inheritDoc}
     */
    protected string $quoteIdentifierTo = '``';

    /**
     * NOTE: Include dashes for MySQL only, need tests for others platforms
     */
    protected string $quoteIdentifierFragmentPattern = '/([^0-9,a-z,A-Z$_\-:])/i';

    public function __construct(
        protected readonly DriverInterface|mysqli|PDO $driver,
    ) {}

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getName(): string
    {
        return self::PLATFORM_NAME;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getSqlPlatformDecorator(): PlatformDecoratorInterface
    {
        return new Sql\Platform();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteIdentifierChain(array|string $identifierChain): string
    {
        return '`' . implode('`.`', (array) str_replace('`', '``', $identifierChain)) . '`';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteTrustedValue(int|float|string|bool $value): ?string
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        return $quotedViaDriverValue ?? parent::quoteTrustedValue($value);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue(string $value): string
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        return $quotedViaDriverValue ?? parent::quoteValue($value);
    }

    protected function quoteViaDriver(string $value): ?string
    {
        if ($this->driver instanceof DriverInterface) {
            // todo: verify this can not return a PDOStatement instance
            $resource = $this->driver->getConnection()->getResource();
        } else {
            $resource = $this->driver;
        }

        if ($resource instanceof mysqli) {
            return '\'' . $resource->real_escape_string($value) . '\'';
        }

        if ($resource instanceof PDO) {
            return $resource->quote($value);
        }

        return null;
    }
}
