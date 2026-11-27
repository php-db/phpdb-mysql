<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\CreateTable as StandardCreateTable;

/**
 * MySQL renders each column with the storage and format options it accepts after the type.
 */
final class CreateTable extends StandardCreateTable
{
    use ColumnOptionTrait;

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function processColumns(?PlatformInterface $adapterPlatform = null): ?array
    {
        if (! $this->columns) {
            return null;
        }

        $platform = $adapterPlatform;

        $sqls = [];

        /** @var array<array-key, ColumnInterface> $columns */
        $columns = $this->columns;

        foreach ($columns as $i => $column) {
            $options = $column->getOptions();

            $sqls[$i] = $this->processColumnOptions(
                $this->processExpression($column, $platform),
                $options,
                $platform,
            );
        }

        return [$sqls];
    }
}
