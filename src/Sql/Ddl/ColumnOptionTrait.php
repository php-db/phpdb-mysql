<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Mysql\Sql\ColumnFormatEnum;
use PhpDb\Mysql\Sql\StorageEnum;
use PhpDb\Sql\Exception\InvalidArgumentException;

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
use function substr_replace;
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
trait ColumnOptionTrait
{
    private const string NAME_PATTERN = '/^[A-Za-z0-9_]+$/';

    /** @var array<string, int> $columnOptionSortOrder Order options are emitted in, lowest first. */
    protected array $columnOptionSortOrder = [
        'unsigned'      => 0,
        'zerofill'      => 1,
        'charset'       => 2,
        'collate'       => 3,
        'identity'      => 4,
        'serial'        => 4,
        'autoincrement' => 4,
        'comment'       => 5,
        'columnformat'  => 6,
        'format'        => 6,
        'storage'       => 7,
        'after'         => 8,
    ];

    /**
     * Appends each option to $sql at the offset its keyword belongs to.
     *
     * @param array<string, mixed> $options
     * @param (callable(string, mixed, ?PlatformInterface): ?array{string, int})|null $resolveExtra
     *        Resolver for options only valid in the calling statement, tried before the common ones.
     */
    protected function processColumnOptions(
        string $sql,
        array $options,
        ?PlatformInterface $platform = null,
        ?callable $resolveExtra = null
    ): string {
        $insertStart = $this->getSqlInsertOffsets($sql);

        uksort($options, $this->compareColumnOptions(...));

        foreach ($options as $name => $value) {
            if (! $value) {
                continue;
            }

            $option   = $this->normalizeColumnOption($name);
            $resolved = $resolveExtra !== null ? $resolveExtra($option, $value, $platform) : null;
            $resolved ??= $this->resolveColumnOption($option, $value, $platform);

            if ($resolved === null) {
                continue;
            }

            [$insert, $j] = $resolved;

            $sql              = substr_replace($sql, $insert, $insertStart[$j], length: 0);
            $insertStartCount = count($insertStart);

            for (; $j < $insertStartCount; ++$j) {
                $insertStart[$j] += strlen($insert);
            }
        }

        return $sql;
    }

    /**
     * @return array{string, int}|null The SQL to insert and the offset index it belongs at.
     * @throws InvalidArgumentException If the option value would not be safe to emit unquoted.
     */
    private function resolveColumnOption(string $option, mixed $value, ?PlatformInterface $platform): ?array
    {
        return match ($option) {
            'unsigned' => [' UNSIGNED', 0],
            'zerofill' => [' ZEROFILL', 0],
            'charset' => [' CHARACTER SET ' . $this->getColumnOptionName('charset', $value), 0],
            'collate' => [' COLLATE ' . $this->getColumnOptionName('collate', $value), 0],
            'identity', 'serial', 'autoincrement' => [' AUTO_INCREMENT', 1],
            'comment' => [' COMMENT ' . $platform->quoteValue($value), 2],
            'columnformat', 'format' => [' COLUMN_FORMAT ' . ColumnFormatEnum::getOptionValue($value)->value, 2],
            'storage' => [' STORAGE ' . StorageEnum::getOptionValue($value)->value, 2],
            default => null,
        };
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
                is_string($value) ? $value : get_debug_type($value)
            ));
        }

        return $value;
    }

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

        foreach (range(0, 3) as $i) {
            $insertStart[$i] ??= $sqlLength;
        }

        /** @var array{0: int, 1: int, 2: int, 3: int} $insertStart */
        return $insertStart;
    }

    private function normalizeColumnOption(string $name): string
    {
        return strtolower(str_replace(['-', '_', ' '], '', $name));
    }

    private function compareColumnOptions(string $columnA, string $columnB): int
    {
        $columnA = $this->normalizeColumnOption($columnA);
        $columnA = $this->columnOptionSortOrder[$columnA] ?? count($this->columnOptionSortOrder);

        $columnB = $this->normalizeColumnOption($columnB);
        $columnB = $this->columnOptionSortOrder[$columnB] ?? count($this->columnOptionSortOrder);

        return $columnA - $columnB;
    }
}
