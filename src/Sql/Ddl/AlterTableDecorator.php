<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

use function count;
use function range;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr_replace;
use function uksort;

// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
final class AlterTableDecorator extends AlterTable implements PlatformDecoratorInterface
{
    protected SqlInterface|PreparableSqlInterface|null $subject;

    /** @var array{
     *  unsigned: int,
     *  zerofill: int,
     *  charset: int,
     *  collate: int,
     *  identity: int,
     *  serial: int,
     *  autoincrement: int,
     *  comment: int,
     *  columnformat: int,
     *  format: int,
     *  storage: int,
     *  after: int
     * } $columnOptionSortOrder
     */
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

    #[Override]
    public function setSubject(
        SqlInterface|PreparableSqlInterface|null $subject,
    ): PlatformDecoratorInterface {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return array<int, int>
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

        return $insertStart;
    }

    /**
     * @return array<int, array<int|string, string>>
     */
    #[Override]
    protected function processAddColumns(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];

        foreach ($this->addColumns as $i => $column) {
            $sql           = $this->processExpression($column, $adapterPlatform);
            $insertStart   = $this->getSqlInsertOffsets($sql);
            $columnOptions = $column->getOptions();

            uksort($columnOptions, [$this, 'compareColumnOptions']);

            foreach ($columnOptions as $coName => $coValue) {
                $insert = '';

                if (! $coValue) {
                    continue;
                }

                switch ($this->normalizeColumnOption($coName)) {
                    case 'unsigned':
                        $insert = ' UNSIGNED';
                        $j      = 0;
                        break;
                    case 'zerofill':
                        $insert = ' ZEROFILL';
                        $j      = 0;
                        break;
                    case 'charset':
                        $insert = " CHARACTER SET {$coValue}";
                        $j      = 0;
                        break;
                    case 'collate':
                        $insert = " COLLATE {$coValue}";
                        $j      = 0;
                        break;
                    case 'identity':
                    case 'serial':
                    case 'autoincrement':
                        $insert = ' AUTO_INCREMENT';
                        $j      = 1;
                        break;
                    case 'comment':
                        $insert = " COMMENT {$adapterPlatform->quoteValue($coValue)}";
                        $j      = 2;
                        break;
                    case 'columnformat':
                    case 'format':
                        $insert = ' COLUMN_FORMAT ' . strtoupper($coValue);
                        $j      = 2;
                        break;
                    case 'storage':
                        $insert = ' STORAGE ' . strtoupper($coValue);
                        $j      = 2;
                        break;
                    case 'after':
                        $insert = " AFTER {$adapterPlatform->quoteIdentifier($coValue)}";
                        $j      = 2;
                }

                if ($insert) {
                    $j                ??= 0;
                    $sql              = substr_replace($sql, $insert, $insertStart[$j], length: 0);
                    $insertStartCount = count($insertStart);
                    for (; $j < $insertStartCount; ++$j) {
                        $insertStart[$j] += strlen($insert);
                    }
                }
            }
            $sqls[$i] = $sql;
        }
        return [$sqls];
    }

    /**
     * @return array<int, array<int|string, string>>
     */
    #[Override]
    protected function processChangeColumns(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];
        foreach ($this->changeColumns as $name => $column) {
            $sql           = $this->processExpression($column, $adapterPlatform);
            $insertStart   = $this->getSqlInsertOffsets($sql);
            $columnOptions = $column->getOptions();

            uksort($columnOptions, [$this, 'compareColumnOptions']);

            foreach ($columnOptions as $coName => $coValue) {
                $insert = '';

                if (! $coValue) {
                    continue;
                }

                switch ($this->normalizeColumnOption($coName)) {
                    case 'unsigned':
                        $insert = ' UNSIGNED';
                        $j      = 0;
                        break;
                    case 'zerofill':
                        $insert = ' ZEROFILL';
                        $j      = 0;
                        break;
                    case 'charset':
                        $insert = " CHARACTER SET {$coValue}";
                        $j      = 0;
                        break;
                    case 'collate':
                        $insert = " COLLATE {$coValue}";
                        $j      = 0;
                        break;
                    case 'identity':
                    case 'serial':
                    case 'autoincrement':
                        $insert = ' AUTO_INCREMENT';
                        $j      = 1;
                        break;
                    case 'comment':
                        $insert = " COMMENT {$adapterPlatform->quoteValue($coValue)}";
                        $j      = 2;
                        break;
                    case 'columnformat':
                    case 'format':
                        $insert = ' COLUMN_FORMAT ' . strtoupper($coValue);
                        $j      = 2;
                        break;
                    case 'storage':
                        $insert = ' STORAGE ' . strtoupper($coValue);
                        $j      = 2;
                        break;
                }

                if ($insert) {
                    $j                ??= 0;
                    $sql              = substr_replace($sql, $insert, $insertStart[$j], length: 0);
                    $insertStartCount = count($insertStart);
                    for (; $j < $insertStartCount; ++$j) {
                        $insertStart[$j] += strlen($insert);
                    }
                }
            }
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                $sql,
            ];
        }

        return [$sqls];
    }

    /**
     * @param string $columnA
     * @param string $columnB
     * @return int
     */
    // phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
    private function compareColumnOptions($columnA, $columnB)
    {
        $columnA = $this->normalizeColumnOption($columnA);
        $columnA = $this->columnOptionSortOrder[$columnA] ?? count($this->columnOptionSortOrder);

        $columnB = $this->normalizeColumnOption($columnB);
        $columnB = $this->columnOptionSortOrder[$columnB] ?? count($this->columnOptionSortOrder);

        return $columnA - $columnB;
    }

    /**
     * @param string $name
     * @return string
     */
    private function normalizeColumnOption($name)
    {
        return strtolower(str_replace(['-', '_', ' '], replace: '', subject: $name));
    }
}
