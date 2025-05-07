<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Adapter\Mysql\Extension;

use LaminasIntegrationTest\Db\Platform\FixtureLoader;
use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;

use function printf;

final class IntegrationTestStoppedListener implements FinishedSubscriber
{
    /** @var FixtureLoader[] */
    private array $fixtureLoaders = [];

    public function notify(Finished $event): void
    {
        if (
            $event->testSuite()->name() !== 'integration test'
            || empty($this->fixtureLoaders)
        ) {
            return;
        }

        printf("\nIntegration test ended.\n");

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->dropDatabase();
        }
    }
}
