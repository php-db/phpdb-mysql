<?php

namespace Laminas\Db\Adapter\Mysql;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdapterServiceFactory implements FactoryInterface
{
    /**
     * Create db adapter service
     *
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AdapterInterface
    {
        $config = $container->get('config');
        return new Adapter($config['db']);
    }
}
