<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Mysql\Metadata;
use Psr\Container\ContainerInterface;

final class MetadataInterfaceFactory
{
    public const ADAPTER_SERVICE_NAME = 'adapter_service_name';
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): MetadataInterface&Metadata\Source {
        $adapterServiceName = $options[self::ADAPTER_SERVICE_NAME] ?? AdapterInterface::class;

        return new Metadata\Source($container->get($adapterServiceName));
    }
}
