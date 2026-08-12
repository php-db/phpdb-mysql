<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Exception;
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

// @mago-expect lint:kan-defect
final class CreateTableDecorator extends CreateTable implements PlatformDecoratorInterface
{
    // @mago-expect analysis:write-only-property - read by the inherited AbstractSql::$subject handling
    // (get_object_vars($this->subject)), since CreateTable extends AbstractSql
    protected SqlInterface|PreparableSqlInterface|null $subject = null;

    /** @var array<string, int> */
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
    ];

    #[Override]
    public function setSubject(
        PreparableSqlInterface|SqlInterface|null $subject,
    ): PlatformDecoratorInterface {
        $this->subject = $subject;

        return $this;
    }

    /**
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
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    protected function processColumns(?PlatformInterface $adapterPlatform = null): ?array
    {
        if (! $this->columns) {
            return null;
        }

        if (null === $adapterPlatform) {
            throw new Exception\RuntimeException('Cannot build column SQL without a platform.');
        }

        $sqls = [];

        foreach ($this->columns as $i => $column) {
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

    // phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
    private function compareColumnOptions(string $columnA, string $columnB): int
    {
        $columnA = $this->normalizeColumnOption($columnA);
        $columnA = $this->columnOptionSortOrder[$columnA] ?? count($this->columnOptionSortOrder);

        $columnB = $this->normalizeColumnOption($columnB);
        $columnB = $this->columnOptionSortOrder[$columnB] ?? count($this->columnOptionSortOrder);

        return $columnA - $columnB;
    }

    private function normalizeColumnOption(string $name): string
    {
        return strtolower(str_replace(['-', '_', ' '], replace: '', subject: $name));
    }
}
