<?php

declare(strict_types=1);

namespace PhpDbTest\Mysql\Sql\Ddl\TestAsset;

/**
 * Column option sets shared by the decorator characterisation tests.
 *
 * Each set is paired with the exact SQL it produces in the test that uses it,
 * pinning option ordering, spacing and insert offsets so that a change in
 * generated DDL cannot pass unnoticed.
 */
final class ColumnOptionMatrix
{
    /** @return array<string, array<string, bool|string>> */
    public static function all(): array
    {
        return [
            'all options'      => [
                'unsigned'       => true,
                'zerofill'       => true,
                'charset'        => 'utf8mb4',
                'collate'        => 'utf8mb4_bin',
                'auto_increment' => true,
                'comment'        => 'here',
                'column_format'  => 'dynamic',
                'storage'        => 'memory',
                'after'          => 'id',
            ],
            'charset collate'  => ['charset' => 'utf8mb3', 'collate' => 'utf8mb3_unicode_ci'],
            'format storage'   => ['column_format' => 'fixed', 'storage' => 'disk'],
            'reverse declared' => [
                'storage'  => 'DISK',
                'comment'  => 'c',
                'unsigned' => true,
                'charset'  => 'latin1',
            ],
            'unknown option'   => ['charset' => 'utf8mb4', 'nonsense' => 'ignored'],
            'after only'       => ['after' => 'other_col'],
            'falsy skipped'    => ['charset' => '', 'unsigned' => false, 'collate' => 'utf8mb4_bin'],
        ];
    }

    /**
     * @param array<string, string> $expected Keyed by the option set name.
     * @return array<string, array{array<string, bool|string>, string}>
     */
    public static function pairedWith(array $expected): array
    {
        $cases = [];
        foreach (self::all() as $name => $options) {
            $cases[$name] = [$options, $expected[$name]];
        }

        return $cases;
    }
}
