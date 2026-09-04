<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql;

/**
 * Keywords accepted by the STORAGE column option.
 *
 * @see https://dev.mysql.com/doc/refman/8.4/en/create-table.html
 */
enum StorageEnum: string
{
    case Disk   = 'DISK';
    case Memory = 'MEMORY';
}
