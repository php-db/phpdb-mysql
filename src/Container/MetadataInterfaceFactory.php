<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Mysql\Metadata;
use Psr\Container\ContainerInterface;

final class MetadataInterfaceFactory
{
    public function __invoke(ContainerInterface $container): MetadataInterface&Metadata\Source
    {
        $adapterInterface = $container->get(AdapterInterface::class);
        return new Metadata\Source($adapterInterface);
    }
}
