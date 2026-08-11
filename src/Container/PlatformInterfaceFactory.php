<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Driver as MysqliDriver;
use PhpDb\Mysql\Pdo\Driver as PdoDriver;
use Psr\Container\ContainerInterface;

final class PlatformInterfaceFactory
{
    /**
     * @param array<string, mixed>|null $options
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    // @mago-expect analysis:unused-parameter
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): PlatformInterface&AdapterPlatform {
        $driverInstance = $options['driver'] ?? null;
        if (! $driverInstance instanceof MysqliDriver && ! $driverInstance instanceof PdoDriver) {
            throw ContainerException::forService(
                AdapterPlatform::class,
                self::class,
                '$options["driver"] must be an instance of ' . MysqliDriver::class . ' or ' . PdoDriver::class . '.',
            );
        }
        return new AdapterPlatform($driverInstance);
    }
}
