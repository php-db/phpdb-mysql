<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Driver\Pdo;

use PhpDbIntegrationTest\Adapter\Mysql\Container\TestAsset\SetUpTrait;
use PhpDbIntegrationTest\Adapter\Mysql\Driver\Pdo\AbstractAdapterTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class AdapterTest extends AbstractAdapterTestCase
{
    use SetUpTrait;
}
