<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use BackedEnum;
use Closure;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Mysql\Sql\ColumnFormatEnum;
use PhpDb\Mysql\Sql\StorageEnum;
use PhpDb\Sql\Exception\InvalidArgumentException;
use ValueError;

use function array_flip;
use function array_key_exists;
use function array_keys;
use function count;
use function get_debug_type;
use function is_string;
use function preg_match;
use function range;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr_replace;
use function trim;
use function uksort;

/**
 * Renders MySQL column options into a generated column definition.
 *
 * Every option that reaches the produced DDL is resolved here, so an option
 * that MySQL takes unquoted is validated in exactly one place. Character set
 * and collation names are emitted as bare identifiers and cannot be escaped,
 * while COLUMN_FORMAT and STORAGE are restricted to the keywords their enums
 * declare.
 *
 * @see https://dev.mysql.com/doc/refman/8.4/en/create-table.html
 *
 * @internal
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
trait ColumnOptionTrait
{
    private const string NAME_PATTERN = '/^[A-Za-z0-9_]+$/';

    /**
     * Column options in emission order, each mapped to its insert slot and SQL template.
     *
     * The slot indexes the array returned by {@see getSqlInsertOffsets()}, so an option's
     * position here decides both where it lands in the definition and its order among
     * the options sharing that slot. The template receives the resolved value as its
     * sole sprintf argument, which flag options simply ignore.
     *
     * @var array<string, array{int<0, 3>, string}>
     */
    private const array COLUMN_OPTIONS = [
        'unsigned'      => [0, ' UNSIGNED'],
        'zerofill'      => [0, ' ZEROFILL'],
        'charset'       => [0, ' CHARACTER SET %s'],
        'collate'       => [0, ' COLLATE %s'],
        'identity'      => [1, ' AUTO_INCREMENT'],
        'serial'        => [1, ' AUTO_INCREMENT'],
        'autoincrement' => [1, ' AUTO_INCREMENT'],
        'comment'       => [2, ' COMMENT %s'],
        'columnformat'  => [2, ' COLUMN_FORMAT %s'],
        'format'        => [2, ' COLUMN_FORMAT %s'],
        'storage'       => [2, ' STORAGE %s'],
        'after'         => [2, ' AFTER %s'],
    ];

    /**
     * Offsets keyed by how late in the definition an option may be inserted.
     *
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    protected function getSqlInsertOffsets(string $sql): array
    {
        $sqlLength   = strlen($sql);
        $insertStart = [];

        foreach (['NOT NULL', 'NULL', 'DEFAULT', 'UNIQUE', 'PRIMARY', 'REFERENCES'] as $needle) {
            $insertPos = strpos($sql, " {$needle}");

            if (false !== $insertPos) {
                switch ($needle) {
                    case 'REFERENCES':
                        $insertStart[2] ??= $insertPos;
                    // no break
                    case 'PRIMARY':
                    case 'UNIQUE':
                        $insertStart[1] ??= $insertPos;
                    // no break
                    default:
                        $insertStart[0] ??= $insertPos;
                }
            }
        }

        foreach (range(
            start: 0,
            end: 3,
        ) as $i) {
            $insertStart[$i] ??= $sqlLength;
        }

        /** @var array{0: int, 1: int, 2: int, 3: int} $insertStart */
        return $insertStart;
    }

    /**
     * Appends each option to $sql at the offset its keyword belongs to.
     *
     * @param array<string, mixed> $options
     * @param (callable(string, mixed, PlatformInterface): ?string)|null $resolveExtra
     *        Value resolver for options only valid in the calling statement, tried before the common one.
     * @throws InvalidArgumentException If an option value would not be safe to emit unquoted.
     * @throws ValueError If a COLUMN_FORMAT or STORAGE value is not a keyword its enum declares.
     */
    protected function processColumnOptions(
        string $sql,
        array $options,
        PlatformInterface $platform,
        ?callable $resolveExtra = null,
    ): string {
        $insertStart = $this->getSqlInsertOffsets($sql);

        uksort($options, $this->compareColumnOptions(...));

        // @mago-expect analysis:mixed-assignment
        foreach ($options as $name => $value) {
            if (! $value) {
                continue;
            }

            $option = $this->normalizeColumnOption($name);

            if (! array_key_exists($option, self::COLUMN_OPTIONS)) {
                continue;
            }

            $resolved = null === $resolveExtra ? null : $resolveExtra($option, $value, $platform);
            $resolved ??= $this->resolveColumnOptionValue($option, $value, $platform);

            if (null === $resolved) {
                continue;
            }

            [$j, $template] = self::COLUMN_OPTIONS[$option];

            $insert = sprintf($template, $resolved);
            $length = strlen($insert);

            foreach ($insertStart as $slot => $offset) {
                if ($slot < $j) {
                    continue;
                }

                if ($slot === $j) {
                    $sql = substr_replace($sql, $insert, $offset, length: 0);
                }

                $insertStart[$slot] = $offset + $length;
            }
        }

        return $sql;
    }

    private function compareColumnOptions(string $columnA, string $columnB): int
    {
        $sortOrder = array_flip(array_keys(self::COLUMN_OPTIONS));
        $unknown   = count($sortOrder);

        $columnA = $sortOrder[$this->normalizeColumnOption($columnA)] ?? $unknown;
        $columnB = $sortOrder[$this->normalizeColumnOption($columnB)] ?? $unknown;

        return $columnA - $columnB;
    }

    /**
     * Backed enums match case-sensitively, so the value is upper-cased before it is handed to the enum.
     *
     * @param Closure(string): BackedEnum $from The enum's from() method, which validates the keyword.
     * @return string The keyword to emit, as declared by the matching enum case.
     * @throws InvalidArgumentException If the value is not a string.
     * @throws ValueError If the value is not one of the declared keywords.
     */
    private function getColumnOptionKeyword(string $option, Closure $from, mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid value for the "%s" column option; expected a keyword string, received "%s"',
                $option,
                get_debug_type($value),
            ));
        }

        return (string) $from(strtoupper(trim($value)))->value;
    }

    /**
     * @return string The validated name, unchanged.
     * @throws InvalidArgumentException If the value is not a bare character set or collation name.
     */
    private function getColumnOptionName(string $option, mixed $value): string
    {
        if (! is_string($value) || preg_match(self::NAME_PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'Invalid value for the "%s" column option; expected an unquoted name matching %s, received "%s"',
                $option,
                self::NAME_PATTERN,
                is_string($value) ? $value : get_debug_type($value),
            ));
        }

        return $value;
    }

    private function normalizeColumnOption(string $name): string
    {
        return strtolower(str_replace(['-', '_', ' '], replace: '', subject: $name));
    }

    /**
     * @return string|null The value to substitute into the option's SQL template, empty for flags,
     *                     or null when the option is only valid in a statement with its own resolver.
     * @throws InvalidArgumentException If the value would not be safe to emit unquoted.
     * @throws ValueError If a COLUMN_FORMAT or STORAGE value is not a keyword its enum declares.
     */
    private function resolveColumnOptionValue(string $option, mixed $value, PlatformInterface $platform): ?string
    {
        return match ($option) {
            'after'                  => null,
            'charset', 'collate'     => $this->getColumnOptionName($option, $value),
            'comment'                => $platform->quoteValue((string) $value),
            'columnformat', 'format' => $this->getColumnOptionKeyword($option, ColumnFormatEnum::from(...), $value),
            'storage'                => $this->getColumnOptionKeyword($option, StorageEnum::from(...), $value),
            default                  => '',
        };
    }
}
