<?php

declare(strict_types=1);

namespace LaminasIntegrationTest\Db\Mysql\Extension;

use LaminasIntegrationTest\Db\Mysql\Platform\FixtureLoader;
use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;

final class IntegrationTestStoppedListener implements FinishedSubscriber
{
    /** @var FixtureLoader[] */
    private $fixtureLoaders = [];

    public function notify(Finished $event): void
    {
        if (
            $event->testSuite()->name() !== 'integration test'
            || empty($this->fixtureLoaders)
        ) {
            return;
        }

        print "\nIntegration test ended.\n";

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->dropDatabase();
        }
    }
}
