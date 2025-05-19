<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql;

use Laminas\Db\Adapter\Driver\DriverInterface;

trait DatabasePlatformNameTrait
{
    /**
     * Get database platform name
     *
     * @param string $nameFormat
     * @return string
     */
    public function getDatabasePlatformName(string $nameFormat = DriverInterface::NAME_FORMAT_CAMELCASE): string
    {
        if ($nameFormat === DriverInterface::NAME_FORMAT_CAMELCASE) {
            return 'Mysql';
        }

        return 'MySQL';
    }
}
