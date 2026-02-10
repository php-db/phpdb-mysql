<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\Driver\PdoConnectionInterface;
use PhpDb\Adapter\Exception\InvalidConnectionParametersException;
use PhpDb\Mysql\Pdo\Connection;
use Psr\Container\ContainerInterface;

use function is_array;

final class PdoConnectionInterfaceFactory
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): PdoConnectionInterface&Connection {
        if (! is_array($options['connection']) || $options['connection'] === []) {
            throw new InvalidConnectionParametersException(
                'Connection configuration must be an array of parameters passed via $options["connection"]',
                $options['connection']
            );
        }

        return new Connection($options['connection']);
    }
}
