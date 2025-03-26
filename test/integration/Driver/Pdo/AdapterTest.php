<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Pdo;

use PHPUnit\Framework\Attributes;

#[Attributes\Group('integration')]
#[Attributes\Group('integration-pdo')]
#[Attributes\CoversNothing()]
final class AdapterTest extends AbstractAdapterTestCase
{
    use AdapterTrait;

    public const DB_SERVER_PORT = 3306;
}
