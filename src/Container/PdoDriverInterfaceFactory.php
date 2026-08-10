<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Driver\Pdo\Result;
use PhpDb\Adapter\Driver\Pdo\Statement;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Exception\ContainerException;
use PhpDb\Mysql\Pdo\Connection;
use PhpDb\Mysql\Pdo\Driver;
use Psr\Container\ContainerInterface;

use function array_key_exists;

final class PdoDriverInterfaceFactory
{
    /**
     * @throws \Laminas\ServiceManager\Exception\ExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface&ServiceManager $container,
        string $requestedName,
        ?array $options = null,
    ): PdoDriverInterface&Driver {
        if (null === $options || ! array_key_exists('connection', $options)) {
            throw ContainerException::forService(
                Driver::class,
                self::class,
                '$options["connection"] must contain an array of connection configuration.',
            );
        }
        $connectionInstance = $container->build(Connection::class, $options);

        $statementInstance = $container->build(
            Statement::class,
            $options['options'] ?? [],
        );

        /** @var ResultInterface&Result $resultInstance */
        $resultInstance = $container->has(ResultInterface::class)
            ? $container->get(ResultInterface::class)
            : new Result();

        return new Driver(
            $connectionInstance,
            $statementInstance,
            $resultInstance,
            $options['pdo_features'] ?? [],
        );
    }
}
