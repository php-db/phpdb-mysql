<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\FixtureLoader;

interface FixtureLoaderInterface
{
    public function createDatabase(): void;

    public function dropDatabase(): void;
}
