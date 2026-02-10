<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Pdo;

use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PhpDbIntegrationTest\Mysql\Pdo\AbstractAdapterTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class AdapterTest extends AbstractAdapterTestCase
{
    use SetupTrait;
}
