<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Mysql\Connection;
use Psr\Container\ContainerInterface;

use function is_array;

final class ConnectionInterfaceFactory
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): ConnectionInterface&Connection {
        $conn = $options['connection'] ?? [];
        if (! is_array($conn) || [] === $conn) {
            throw new InvalidConnectionParametersException(
                'Connection configuration must be an array of parameters passed via $options["connection"]',
                $conn,
            );
        }

        return new Connection($conn);
    }
}
