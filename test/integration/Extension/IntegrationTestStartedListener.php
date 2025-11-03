<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Adapter\Mysql\Extension;

use Exception;
use PhpDbIntegrationTest\Adapter\Mysql\FixtureLoader\FixtureLoaderInterface;
use PhpDbIntegrationTest\Adapter\Mysql\FixtureLoader\MysqlFixtureLoader;
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

        if (empty($this->fixtureLoaders)) {
            return;
        }

        printf("\nIntegration test started.\n");

        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->createDatabase();
        }
    }
}
