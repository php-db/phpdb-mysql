<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement as PdoStatement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Mysql\Driver;
use PhpDb\Adapter\Mysql\Metadata\Source\MysqlMetadata;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Profiler;
use PhpDb\Container\AdapterAbstractServiceFactory;
use PhpDb\Container\ConnectionInterfaceFactoryFactoryInterface;
use PhpDb\Container\DriverInterfaceFactoryFactoryInterface;
use PhpDb\Container\PlatformInterfaceFactoryFactoryInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\ResultSet;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'abstract_factories' => [
                AdapterAbstractServiceFactory::class,
            ],
            'aliases'            => [
                'MySqli'                                          => Driver\Mysqli\Mysqli::class,
                'MySQLi'                                          => Driver\Mysqli\Mysqli::class,
                'Mysqli'                                          => Driver\Mysqli\Mysqli::class,
                'mysqli'                                          => Driver\Mysqli\Mysqli::class,
                'PDO_MySQL'                                       => Driver\Pdo\Pdo::class,
                'Pdo_MySQL'                                       => Driver\Pdo\Pdo::class,
                'Pdo_Mysql'                                       => Driver\Pdo\Pdo::class,
                'pdo_mysql'                                       => Driver\Pdo\Pdo::class,
                'pdomysql'                                        => Driver\Pdo\Pdo::class,
                'pdodriver'                                       => Driver\Pdo\Pdo::class,
                'pdo'                                             => Driver\Pdo\Pdo::class,
                DriverInterface::class                            => Driver\Mysqli\Mysqli::class,
                PdoDriverInterface::class                         => Driver\Pdo\Pdo::class,
                Profiler\ProfilerInterface::class                 => Profiler\Profiler::class,
                ResultSet\ResultSetInterface::class               => ResultSet\ResultSet::class,
                ConnectionInterfaceFactoryFactoryInterface::class => Container\ConnectionInterfaceFactoryFactory::class,
                DriverInterfaceFactoryFactoryInterface::class     => Container\DriverInterfaceFactoryFactory::class,
                MetadataInterface::class                          => MysqlMetadata::class,
                PlatformInterfaceFactoryFactoryInterface::class   => Container\PlatformInterfaceFactoryFactory::class,
            ],
            'factories'          => [
                AdapterInterface::class         => Container\AdapterFactory::class,
                Driver\Mysqli\Mysqli::class     => Container\MysqliDriverFactory::class,
                Driver\Mysqli\Connection::class => Container\MysqliConnectionFactory::class,
                Driver\Mysqli\Statement::class  => Container\MysqliStatementFactory::class,
                Driver\Pdo\Pdo::class           => Container\PdoDriverFactory::class,
                Driver\Pdo\Connection::class    => Container\PdoConnectionFactory::class,
                MysqlMetadata::class            => Container\MetadataInterfaceFactory::class,
                PdoStatement::class             => Container\PdoStatementFactory::class,
                PlatformInterface::class        => Container\PlatformInterfaceFactory::class,
            ],
            'invokables'         => [
                Container\ConnectionInterfaceFactoryFactory::class
                    => Container\ConnectionInterfaceFactoryFactory::class,
                Container\DriverInterfaceFactoryFactory::class
                    => Container\DriverInterfaceFactoryFactory::class,
                Container\PlatformInterfaceFactoryFactory::class
                    => Container\PlatformInterfaceFactoryFactory::class,
                Driver\Mysqli\Result::class
                    => Driver\Mysqli\Result::class,
                Profiler\Profiler::class
                    => Profiler\Profiler::class,
                Result::class
                    => Result::class,
                ResultSet\ResultSet::class
                    => ResultSet\ResultSet::class,
            ],
        ];
    }
}
