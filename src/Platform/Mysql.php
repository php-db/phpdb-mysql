<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Platform;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Adapter\Exception\InvalidArgumentException;
use Laminas\Db\Adapter\Platform\AbstractPlatform;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli;
use Laminas\Db\Adapter\Mysql\Driver\Pdo;
use Laminas\Db\Adapter\Mysql\Sql\Platform\Mysql\Mysql as SqlPlatform;
use Laminas\Db\Sql\Platform\PlatformDecoratorInterface;

use function implode;
use function str_replace;

class Mysql extends AbstractPlatform
{
    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifier = ['`', '`'];

    /**
     * {@inheritDoc}
     */
    protected $quoteIdentifierTo = '``';

    /** @var \mysqli|\PDO|Pdo\Pdo|Mysqli\Mysqli */
    protected $driver;

    /**
     * NOTE: Include dashes for MySQL only, need tests for others platforms
     *
     * @var string
     */
    protected $quoteIdentifierFragmentPattern = '/([^0-9,a-z,A-Z$_\-:])/i';

    /**
     * todo: track down if this still needs to accept null
     */
    public function __construct(
        ?DriverInterface $driver = null
    ) {
        if ($driver) {
            $this->setDriver($driver);
        }
    }

    /**
     * @param \Laminas\Db\Adapter\Driver\Mysqli\Mysqli|\Laminas\Db\Adapter\Driver\Pdo\Pdo|\mysqli|\PDO $driver
     * @return $this Provides a fluent interface
     * @throws InvalidArgumentException
     */
    public function setDriver($driver)
    {
        // handle Laminas\Db drivers
        if (
            $driver instanceof Mysqli\Mysqli
            || ($driver instanceof Pdo\Pdo && $driver->getDatabasePlatformName() === 'Mysql')
            || $driver instanceof \mysqli
            || ($driver instanceof \PDO && $driver->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql')
        ) {
            $this->driver = $driver;
            return $this;
        }

        throw new Exception\InvalidArgumentException(
            '$driver must be a Laminas\Db\Adapter\Mysql\Driver\*, Mysqli\Mysqli or Pdo\Pdo instance'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'MySQL';
    }

    public function getSqlPlatformDecorator(): PlatformDecoratorInterface
    {
        return new SqlPlatform();
    }

    /**
     * {@inheritDoc}
     */
    public function quoteIdentifierChain($identifierChain)
    {
        return '`' . implode('`.`', (array) str_replace('`', '``', $identifierChain)) . '`';
    }

    /**
     * {@inheritDoc}
     */
    public function quoteValue($value)
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        return $quotedViaDriverValue ?? parent::quoteValue($value);
    }

    /**
     * {@inheritDoc}
     */
    public function quoteTrustedValue($value)
    {
        $quotedViaDriverValue = $this->quoteViaDriver($value);

        return $quotedViaDriverValue ?? parent::quoteTrustedValue($value);
    }

    /**
     * @param  string $value
     * @return string|null
     */
    protected function quoteViaDriver($value)
    {
        if ($this->driver instanceof DriverInterface) {
            // todo: verify this can not return a PDOStatement instance
            $resource = $this->driver->getConnection()->getResource();
        } else {
            $resource = $this->driver;
        }

        if ($resource instanceof \mysqli) {
            return '\'' . $resource->real_escape_string($value) . '\'';
        }
        if ($resource instanceof \PDO) {
            return $resource->quote($value);
        }

        return null;
    }
}
