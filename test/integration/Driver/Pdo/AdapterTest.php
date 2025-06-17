<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo;

use LaminasIntegrationTest\Db\Adapter\Mysql\Container\TestAsset\SetUpTrait;
use LaminasIntegrationTest\Db\Adapter\Mysql\Driver\Pdo\AbstractAdapterTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class AdapterTest extends AbstractAdapterTestCase
{
    use SetUpTrait;
}
