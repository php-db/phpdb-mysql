<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Driver\Pdo;

use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class AdapterTest extends AbstractAdapterTestCase
{
    use SetupTrait;
}
