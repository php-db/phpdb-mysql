<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\FixtureLoader;

// phpcs:ignore WebimpressCodingStandard.NamingConventions.Interface.Suffix
interface FixtureLoader
{
    public function createDatabase();

    public function dropDatabase();
}
