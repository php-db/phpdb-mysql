<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Mysql\Metadata\Source\MysqlMetadata;
use PhpDb\Metadata\MetadataInterface;
use Psr\Container\ContainerInterface;

final class MetadataInterfaceFactory
{
    public function __invoke(ContainerInterface $container): MetadataInterface
    {
        $adapterInterface = $container->get(AdapterInterface::class);
        return new MysqlMetadata($adapterInterface);
    }
}
