<?php

namespace LaminasIntegrationTest\Db\Mysql\Extension;

use LaminasIntegrationTest\Db\Mysql\Platform\FixtureLoader;
use LaminasIntegrationTest\Db\Mysql\Platform\MysqlFixtureLoader;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;

use function getenv;
use function printf;

final class IntegrationTestStartedListener implements StartedSubscriber
{
    /** @var FixtureLoader[] */
    private $fixtureLoaders = [];

    public function notify(Started $event): void
    {
        if ($event->testSuite()->name() !== 'integration test') {
            return;
        }

        if (getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL')) {
            $this->fixtureLoaders[] = new MysqlFixtureLoader();
        }

        if (empty($this->fixtureLoaders)) {
            return;
        }

        printf("\nIntegration test started.\n");

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->createDatabase();
        }
    }
}
