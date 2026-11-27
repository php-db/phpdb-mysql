<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Select as StandardSelect;

/**
 * MySQL has no OFFSET clause of its own, so an offset without a limit names the maximum unsigned
 * bigint as the limit.
 */
final class Select extends StandardSelect
{
    #[Override]
    protected function prepareSpecifications(): void
    {
        if (null === $this->limit && null !== $this->offset) {
            $this->specifications[self::LIMIT] = 'LIMIT 18446744073709551615';
        }
    }

    /** @return array{0: string}|null */
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
        if ($parameterContainer && null !== $driver) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet("{$paramPrefix}limit", $this->limit, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName("{$paramPrefix}limit")];
        }

        return [(string) $this->limit];
    }

    /** @return array{0: string}|null */
    #[Override]
    protected function processOffset(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
    ): ?array {
        if (null === $this->offset) {
            return null;
        }
        if ($parameterContainer && null !== $driver) {
            $paramPrefix = $this->processInfo['paramPrefix'];
            $parameterContainer->offsetSet("{$paramPrefix}offset", $this->offset, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName("{$paramPrefix}offset")];
        }

        return [(string) $this->offset];
    }
}
