<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\FixtureLoader;

// phpcs:ignore WebimpressCodingStandard.NamingConventions.Interface.Suffix
interface FixtureLoader
{
    public function createDatabase(): void;

    public function dropDatabase(): void;
}
