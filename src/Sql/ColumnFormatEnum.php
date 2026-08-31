<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql;

use PhpDb\Sql\Exception\InvalidArgumentException;

use function array_map;
use function get_debug_type;
use function implode;
use function is_string;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * Keywords accepted by the COLUMN_FORMAT column option.
 *
 * @see https://dev.mysql.com/doc/refman/8.4/en/create-table.html
 */
enum ColumnFormatEnum: string
{
    case Fixed   = 'FIXED';
    case Dynamic = 'DYNAMIC';
    case Default = 'DEFAULT';

    /**
     * @return self The case whose value is emitted as the COLUMN_FORMAT keyword.
     * @throws InvalidArgumentException If the value is not one of the declared keywords.
     */
    public static function getOptionValue(mixed $value): self
    {
        $keyword = is_string($value) ? strtoupper(trim($value)) : '';
        $format  = self::tryFrom($keyword);

        if (null === $format) {
            throw new InvalidArgumentException(sprintf(
                'Invalid value for the "columnformat" column option; expected one of %s, received "%s"',
                implode(', ', array_map(static fn(self $case): string => $case->value, self::cases())),
                is_string($value) ? $value : get_debug_type($value),
            ));
        }

        return $format;
    }
}
