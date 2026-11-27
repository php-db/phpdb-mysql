<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\AlterTable as StandardAlterTable;
use PhpDb\Sql\Ddl\Column\ColumnInterface;

/**
 * MySQL renders each added or changed column with the storage and format options it accepts after
 * the type.
 */
final class AlterTable extends StandardAlterTable
{
    use ColumnOptionTrait;

    /**
     * @return array{0: list<string>}
     */
    #[Override]
    protected function processAddColumns(?PlatformInterface $adapterPlatform = null): array
    {
        /** @var PlatformInterface $platform */
        $platform = $adapterPlatform;

        $sqls = [];

        /** @var array<array-key, ColumnInterface> $addColumns */
        $addColumns = $this->addColumns;

        foreach ($addColumns as $column) {
            $options = $column->getOptions();

            $sqls[] = $this->processColumnOptions(
                $this->processExpression($column, $platform),
                $options,
                $platform,
                $this->resolveAfterOption(...),
            );
        }

        return [$sqls];
    }

    /**
     * @return array{0: list<array{string, string}>}
     */
    #[Override]
    protected function processChangeColumns(?PlatformInterface $adapterPlatform = null): array
    {
        /** @var PlatformInterface $platform */
        $platform = $adapterPlatform;

        $sqls = [];

        $changeColumns = $this->changeColumns;

        foreach ($changeColumns as $name => $column) {
            $options = $column->getOptions();

            $sqls[] = [
                $platform->quoteIdentifier($name),
                $this->processColumnOptions(
                    $this->processExpression($column, $platform),
                    $options,
                    $platform,
                ),
            ];
        }

        return [$sqls];
    }

    private function resolveAfterOption(string $option, mixed $value, PlatformInterface $platform): ?string
    {
        if ('after' !== $option) {
            return null;
        }

        return $platform->quoteIdentifier((string) $value);
    }
}
