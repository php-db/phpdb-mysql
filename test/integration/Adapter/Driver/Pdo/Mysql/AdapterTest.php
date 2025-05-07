<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo\Mysql;

use LaminasIntegrationTest\Db\Adapter\Driver\Pdo\AbstractAdapterTestCase;
use LaminasIntegrationTest\Db\Adapter\Driver\Pdo\AdapterTrait as BaseAdapterTrait;

final class AdapterTest extends AbstractAdapterTestCase
{
    use AdapterTrait;
    use BaseAdapterTrait;

    public ?int $port = 3306;
}
