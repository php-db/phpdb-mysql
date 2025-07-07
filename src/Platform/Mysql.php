<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Platform;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Mysql\Sql\Platform\Mysql\Mysql as SqlPlatform;
use PhpDb\Adapter\Platform\AbstractPlatform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use mysqli;
use Override;
use PDO;

use function implode;
use function str_replace;

class Mysql extends AbstractPlatform
{
    public final const PLATFORM_NAME = 'MySQL';

    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifier = ['`', '`'];

    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifierTo = '``';

    /**
     * NOTE: Include dashes for MySQL only, need tests for others platforms
     *
     * @var string
     */
    protected $quoteIdentifierFragmentPattern = '/([^0-9,a-z,A-Z$_\-:])/i';

    public function __construct(
        protected readonly DriverInterface|mysqli|PDO $driver
    ) {
    }

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
        return new SqlPlatform();
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
    public function quoteValue(string $value): string
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        return $quotedViaDriverValue ?? parent::quoteValue($value);
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
