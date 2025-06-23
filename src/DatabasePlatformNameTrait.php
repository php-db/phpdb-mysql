<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Exception;

trait DatabasePlatformNameTrait
{
    public function getDatabasePlatformName(
        string $nameFormat = DriverInterface::NAME_FORMAT_CAMELCASE
    ): string {
        if ($nameFormat === DriverInterface::NAME_FORMAT_CAMELCASE) {
            return 'Mysql';
        }

        if ($nameFormat === DriverInterface::NAME_FORMAT_NATURAL) {
            return 'MySQL';
        }

        throw new Exception\InvalidArgumentException(
            'Invalid name format provided. Must be one of: '
            . DriverInterface::NAME_FORMAT_CAMELCASE
            . ', '
            . DriverInterface::NAME_FORMAT_NATURAL
        );
    }
}
