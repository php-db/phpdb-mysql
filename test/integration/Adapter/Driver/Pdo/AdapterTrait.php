<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\AdapterInterface;

trait AdapterTrait
{
    protected ?AdapterInterface $adapter = null;
    protected ?string $hostname          = 'localhost';

    public function getAdapter(): AdapterInterface
    {
        if ($this->adapter === null) {
            $this->fail('Adapter not initialized');
        }

        return $this->adapter;
    }

    protected function getHostname(): ?string
    {
        return $this->hostname;
    }
}
