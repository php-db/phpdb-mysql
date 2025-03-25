<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Mysql\Platform;

// phpcs:ignore WebimpressCodingStandard.NamingConventions.Interface.Suffix
interface FixtureLoader
{
    public function createDatabase(): void;

    public function dropDatabase(): void;
}
