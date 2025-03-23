<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Pdo;

use Laminas\Db\Adapter\Adapter;

final class AdapterTest extends AbstractAdapterTestCase
{
    use AdapterTrait;

    /** @var Adapter */
    public const DB_SERVER_PORT = 3306;
}
