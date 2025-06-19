<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Container\TestAsset;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Mysql\ConfigProvider;
use Laminas\Db\Adapter\Mysql\Driver\Mysqli\Mysqli;
use Laminas\Db\Adapter\Mysql\Driver\Pdo\Pdo;
use Laminas\Db\Container\AdapterManager;
use Laminas\Db\Container\ConfigProvider as LaminasDbConfigProvider;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;

use function getenv;

/**
 * This trait provides a setup method for integration tests that require
 * a database adapter configuration.
 *
 * It initializes the service manager with a database configuration,
 * allowing for the creation of an adapter manager and the retrieval
 * of an adapter instance.
 */
trait SetupTrait
{
    protected array $config = ['db' => []];

    protected ?AdapterInterface $adapter;

    protected AdapterManager $adapterManager;

    protected DriverInterface|string|null $driver = null;

    protected function setUp(): void
    {
        $this->getAdapter();
        parent::setUp();
    }

    protected function getAdapter(array $config = []): AdapterInterface&Adapter
    {
        $connectionConfig = [
            'db' => [
                'driver'     => $this->driver ?? Pdo::class,
                'connection' => [
                    'hostname'       => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_HOSTNAME') ?: 'localhost',
                    'username'       => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_USERNAME'),
                    'password'       => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_PASSWORD'),
                    'database'       => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_DATABASE'),
                    'port'           => (string) getenv('TESTS_LAMINAS_DB_ADAPTER_MYSQL_PORT') ?: '3306',
                    'charset'        => 'utf8',
                    'driver_options' => [],
                ],
                'options'    => [
                    'buffer_results' => false,
                ],
            ],
        ];

        // merge service config from both Laminas\Db and Laminas\Db\Adapter\Mysql
        $serviceManagerConfig = ArrayUtils::merge(
            (new LaminasDbConfigProvider())()['dependencies'],
            (new ConfigProvider())()['dependencies']
        );

        $serviceManagerConfig = ArrayUtils::merge(
            $serviceManagerConfig,
            $connectionConfig
        );

        // prefer passed config over environment variables
        if ($config !== []) {
            $serviceManagerConfig = ArrayUtils::merge($serviceManagerConfig, $config);
        }

        $serviceManagerConfig = ArrayUtils::merge(
            $serviceManagerConfig,
            [
                'services'   => [
                    'config' => $serviceManagerConfig,
                ],
            ]
        );

        $this->config         = $serviceManagerConfig;
        $container            = new ServiceManager($this->config);
        $this->adapterManager = $container->get(AdapterManager::class);
        $this->adapter        = $this->adapterManager->get(AdapterInterface::class);

        return $this->adapter;
    }

    protected function getConfig(): array
    {
        return $this->config;
    }

    protected function getHostname(): string
    {
        return $this->getConfig()['db']['connection']['hostname'];
    }
}
