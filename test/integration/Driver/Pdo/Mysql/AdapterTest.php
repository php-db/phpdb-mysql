<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo\Mysql;

use LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo\AbstractAdapterTestCase;
use LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo\AdapterTrait as BaseAdapterTrait;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class AdapterTest extends AbstractAdapterTestCase
{
    use AdapterTrait;
    use BaseAdapterTrait;

    public ?int $port = 3306;
}
