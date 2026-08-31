<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Extension;

use PhpDbIntegrationTest\Mysql\FixtureLoader\FixtureLoaderInterface;
use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;

use function printf;

final class IntegrationTestStoppedListener implements FinishedSubscriber
{
    /** @var FixtureLoaderInterface[] */
    private array $fixtureLoaders = [];

    public function notify(Finished $event): void
    {
        if (
            $event->testSuite()->name() !== 'integration test'
                || [] === $this->fixtureLoaders
        ) {
            return;
        }

        printf("\nIntegration test ended.\n");

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->dropDatabase();
        }
    }
}
