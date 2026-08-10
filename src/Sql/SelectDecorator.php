<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\SqlInterface;

final class SelectDecorator extends Select implements PlatformDecoratorInterface
{
    protected SqlInterface|PreparableSqlInterface|null $subject;

    #[Override]
    public function setSubject(
        SqlInterface|PreparableSqlInterface|null $subject,
    ): PlatformDecoratorInterface {
        $this->subject = $subject;
        return $this;
    }

    #[Override]
    protected function localizeVariables(): void
    {
        parent::localizeVariables();
        if (null === $this->limit && null !== $this->offset) {
            $this->specifications[self::LIMIT] = 'LIMIT 18446744073709551615';
        }
    }

    /** @return string[]|null */
    #[Override]
    protected function processLimit(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->limit && null !== $this->offset) {
            return [''];
        }
        if (null === $this->limit) {
            return null;
        }
        if ($parameterContainer) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet("{$paramPrefix}limit", $this->limit, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName("{$paramPrefix}limit")];
        }

        return [$this->limit];
    }

    #[Override]
    protected function processOffset(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->offset) {
            return null;
        }
        if ($parameterContainer) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet("{$paramPrefix}offset", $this->offset, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName("{$paramPrefix}offset")];
        }

        return [$this->offset];
    }
}
