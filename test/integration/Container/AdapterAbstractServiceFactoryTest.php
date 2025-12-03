<?php

declare(strict_types=1);

namespace PhpDbTest\Adapter;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Mysql\ConfigProvider;
use PhpDb\Adapter\Mysql\Driver\Mysqli\Mysqli;
use PhpDb\Adapter\Mysql\Driver\Pdo\Pdo;
use PhpDb\ConfigProvider as LaminasDbConfigProvider;
use PhpDb\Container\AdapterAbstractServiceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function getenv;

#[RequiresPhpExtension('mysqli')]
#[RequiresPhpExtension('pdo_mysql')]
#[CoversClass(AdapterAbstractServiceFactory::class)]
final class AdapterAbstractServiceFactoryTest extends TestCase
{
    private ContainerInterface $serviceManager;

    #[Override]
    protected function setUp(): void
    {
        $this->serviceManager = $this->buildContainer();
        parent::setUp();
    }

    protected function buildContainer(array $config = []): ContainerInterface
    {
        $readAdapterConfig = [
            'db' => [
                'adapters' => [
                    'PhpDb\Adapter\Reader' => [
                        'driver'     => Pdo::class,
                        'connection' => [
                            'hostname'       => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME') ?: 'localhost',
                            'username'       => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
                            'password'       => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
                            'database'       => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
                            'port'           => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PORT') ?: '3306',
                            'charset'        => 'utf8',
                            'driver_options' => [],
                        ],
                        'options'    => [
                            'buffer_results' => false,
                        ],
                    ],
                ],
            ],
        ];

        $writeAdapterConfig = [
            'db' => [
                'adapters' => [
                    'PhpDb\Adapter\Writer' => [
                        'driver'     => Mysqli::class,
                        'connection' => [
                            'hostname'       => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_HOSTNAME') ?: 'localhost',
                            'username'       => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_USERNAME'),
                            'password'       => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PASSWORD'),
                            'database'       => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_DATABASE'),
                            'port'           => (string) getenv('TESTS_PHPDB_ADAPTER_MYSQL_PORT') ?: '3306',
                            'charset'        => 'utf8',
                            'driver_options' => [],
                        ],
                        'options'    => [
                            'buffer_results' => false,
                        ],
                    ],
                ],
            ],
        ];

        $adapterConfig = ArrayUtils::merge($readAdapterConfig, $writeAdapterConfig);

        // merge service config from both PhpDb and PhpDb\Adapter\Mysql
        $serviceManagerConfig = ArrayUtils::merge(
            (new LaminasDbConfigProvider())()['dependencies'],
            (new ConfigProvider())()['dependencies']
        );

        $serviceManagerConfig = ArrayUtils::merge(
            $serviceManagerConfig,
            $adapterConfig
        );

        // prefer passed config over environment variables
        if ($config !== []) {
            $serviceManagerConfig = ArrayUtils::merge($serviceManagerConfig, $config);
        }

        $serviceManagerConfig = ArrayUtils::merge(
            $serviceManagerConfig,
            [
                'services' => [
                    'config' => $serviceManagerConfig,
                ],
            ]
        );

        $config = $serviceManagerConfig;
        return new ServiceManager($config);
    }

    public static function providerValidService(): array
    {
        return [
            ['PhpDb\Adapter\Writer'],
            ['PhpDb\Adapter\Reader'],
        ];
    }

    public static function providerInvalidService(): array
    {
        return [
            ['PhpDb\Adapter\Unknown'],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[DataProvider('providerValidService')]
    public function testValidService(string $service): void
    {
        $actual = $this->serviceManager->get($service);
        self::assertInstanceOf(AdapterInterface::class, $actual);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[DataProvider('providerInvalidService')]
    public function testInvalidService(string $service): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->serviceManager->get($service);
    }
}
