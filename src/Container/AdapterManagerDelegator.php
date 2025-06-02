<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Container;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\PdoDriverInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\Pdo\Statement as PdoStatement;
use Laminas\Db\Adapter\Mysql\Driver;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Adapter\Profiler;
use Laminas\Db\Container\AdapterManager;
use Laminas\Db\ResultSet;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\Container\ContainerInterface;

final class AdapterManagerDelegator implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
        ?array $options = null
    ): AdapterManager {
        $adapterManager = $callback();
        $adapterManager->configure([
            'aliases'   => [
                'MySqli'                            => Driver\Mysqli\Mysqli::class,
                'MySQLi'                            => Driver\Mysqli\Mysqli::class,
                'mysqli'                            => Driver\Mysqli\Mysqli::class,
                'Pdo_Mysql'                         => Driver\Pdo\Pdo::class,
                'pdo_mysql'                         => Driver\Pdo\Pdo::class,
                'pdomysql'                          => Driver\Pdo\Pdo::class,
                'pdodriver'                         => Driver\Pdo\Pdo::class,
                'pdo'                               => Driver\Pdo\Pdo::class,
                DriverInterface::class              => Driver\Mysqli\Mysqli::class,
                PdoDriverInterface::class           => Driver\Pdo\Pdo::class,
                Profiler\ProfilerInterface::class   => Profiler\Profiler::class,
                ResultSet\ResultSetInterface::class => ResultSet\ResultSet::class,
            ],
            'factories' => [
                AdapterInterface::class         => AdapterFactory::class,
                Driver\Mysqli\Mysqli::class     => MysqliDriverFactory::class,
                Driver\Mysqli\Connection::class => MysqliConnectionFactory::class,
                Driver\Mysqli\Result::class     => MysqliResultFactory::class,
                Driver\Mysqli\Statement::class  => MysqliStatementFactory::class,
                Driver\Pdo\Pdo::class           => PdoDriverFactory::class,
                Driver\Pdo\Connection::class    => PdoConnectionFactory::class,
                Result::class                   => PdoResultFactory::class,
                PdoStatement::class             => PdoStatementFactory::class,
                PlatformInterface::class        => PlatformInterfaceFactory::class,
                Profiler\Profiler::class        => InvokableFactory::class,
                ResultSet\ResultSet::class      => InvokableFactory::class,
            ],
        ]);

        return $adapterManager;
    }
}
