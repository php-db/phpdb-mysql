<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql;

use Override;
use PhpDb\Sql\Platform\AbstractPlatform;
use PhpDb\Sql\TableIdentifier;

final class Platform extends AbstractPlatform
{
    #[Override]
    public function alterTable(string|TableIdentifier $table = ''): Ddl\AlterTable
    {
        return new Ddl\AlterTable($table);
    }

    #[Override]
    public function createTable(string|TableIdentifier $table = ''): Ddl\CreateTable
    {
        return new Ddl\CreateTable($table);
    }

    /** @param TableIdentifier|string|array<string, string|TableIdentifier>|null $table */
    #[Override]
    public function select(array|string|TableIdentifier|null $table = null): Select
    {
        return new Select($table);
    }
}
