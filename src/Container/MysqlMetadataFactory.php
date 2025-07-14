<?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Mysql\Metadata\Source\MysqlMetadata;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Metadata\MetadataInterface;
use Psr\Container\ContainerInterface;

final class MysqlMetadataFactory
{
    public function __invoke(ContainerInterface $container): MetadataInterface&MysqlMetadata
    {
        /** @var AdapterInterface&SchemaAwareInterface $adapter */
        $adapter = $container->get(AdapterInterface::class);

        return new MysqlMetadata(
            $adapter
        );
    }
}
