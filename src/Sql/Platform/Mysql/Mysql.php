<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Sql\Platform\Mysql;

use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Platform\AbstractPlatform;
use PhpDb\Sql\Select;

final class Mysql extends AbstractPlatform
{
    public function __construct()
    {
        $this->setTypeDecorator(Select::class, new SelectDecorator());
        $this->setTypeDecorator(CreateTable::class, new Ddl\CreateTableDecorator());
        $this->setTypeDecorator(AlterTable::class, new Ddl\AlterTableDecorator());
    }
}
