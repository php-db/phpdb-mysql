<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql;

trait DatabasePlatformNameTrait
{
    /**
     * Get database platform name
     *
     * @param  string $nameFormat
     * @return string
     */
    public function getDatabasePlatformName($nameFormat = self::NAME_FORMAT_CAMELCASE)
    {
        if ($nameFormat === self::NAME_FORMAT_CAMELCASE) {
            return 'Mysql';
        }

        return 'MySQL';
    }
}
