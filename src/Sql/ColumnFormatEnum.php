<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql;

/**
 * Keywords accepted by the COLUMN_FORMAT column option.
 *
 * @see https://dev.mysql.com/doc/refman/8.4/en/create-table.html
 */
enum ColumnFormatEnum: string
{
    case Fixed   = 'FIXED';
    case Dynamic = 'DYNAMIC';
    case Default = 'DEFAULT';
}
