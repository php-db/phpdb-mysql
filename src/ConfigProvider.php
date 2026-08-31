<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\Statement as PdoStatement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Metadata\MetadataInterface;

final class ConfigProvider
{
    /**
     * @return array{
     *     aliases: array<string, class-string>,
     *     factories: array<class-string, class-string>,
     * }
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                'MySqli'                  => Driver::class,
                'MySQLi'                  => Driver::class,
                'Mysqli'                  => Driver::class,
                'mysqli'                  => Driver::class,
                'PDO_MySQL'               => Pdo\Driver::class,
                'Pdo_MySQL'               => Pdo\Driver::class,
                'Pdo_Mysql'               => Pdo\Driver::class,
                'pdo_mysql'               => Pdo\Driver::class,
                'pdomysql'                => Pdo\Driver::class,
                'pdodriver'               => Pdo\Driver::class,
                'pdo'                     => Pdo\Driver::class,
                DriverInterface::class    => Driver::class,
                PdoDriverInterface::class => Pdo\Driver::class,
                MetadataInterface::class  => Metadata\Source::class,
            ],
            'factories' => [
                Driver::class            => Container\DriverInterfaceFactory::class,
                Connection::class        => Container\ConnectionInterfaceFactory::class,
                Statement::class         => Container\StatementInterfaceFactory::class,
                Pdo\Driver::class        => Container\PdoDriverInterfaceFactory::class,
                Pdo\Connection::class    => Container\PdoConnectionInterfaceFactory::class,
                Metadata\Source::class   => Container\MetadataInterfaceFactory::class,
                PdoStatement::class      => Container\PdoStatementFactory::class,
                PlatformInterface::class => Container\PlatformInterfaceFactory::class,
            ],
        ];
    }

    /**
     * @return array{
     *     dependencies: array{
     *         aliases: array<string, class-string>,
     *         factories: array<class-string, class-string>,
     *     },
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }
}
