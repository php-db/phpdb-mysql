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
    /**
     * @param array<string, mixed>|null $options
     *
     * @throws \PhpDb\Adapter\Exception\ExceptionInterface
     *
     * @mago-expect analysis:unused-parameter
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): PdoConnectionInterface&Connection {
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
