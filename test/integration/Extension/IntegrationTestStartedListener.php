<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Extension;

use Exception;
use LaminasIntegrationTest\Db\Platform\FixtureLoader;
use LaminasIntegrationTest\Db\Adapter\Mysql\Platform\MysqlFixtureLoader;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;

use function getenv;
use function printf;

final class IntegrationTestStartedListener implements StartedSubscriber
{
    /** @var FixtureLoader[] */
    private array $fixtureLoaders = [];

    /**
     * @throws Exception
     */
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
