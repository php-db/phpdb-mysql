<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Extension;

use Exception;
use PhpDbIntegrationTest\Mysql\FixtureLoader\FixtureLoaderInterface;
use PhpDbIntegrationTest\Mysql\FixtureLoader\MysqlFixtureLoader;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;

use function getenv;
use function printf;

final class IntegrationTestStartedListener implements StartedSubscriber
{
    /** @var FixtureLoaderInterface[] */
    private array $fixtureLoaders = [];

    /**
     * @throws Exception
     */
    public function notify(Started $event): void
    {
        if ($event->testSuite()->name() !== 'integration test') {
            return;
        }

        if (getenv('TESTS_PHPDB_ADAPTER_MYSQL')) {
            $this->fixtureLoaders[] = new MysqlFixtureLoader();
        }

        if ([] === $this->fixtureLoaders) {
            return;
        }

        printf("\nIntegration test started.\n");

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->createDatabase();
        }
    }
}
