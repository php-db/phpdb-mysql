<?php

namespace LaminasIntegrationTest\Db\Mysql\Driver\Mysqli;

use Laminas\Db\Mysql\Driver\Mysqli\Driver;

use function extension_loaded;
use function getenv;
use function is_string;

// phpcs:ignore WebimpressCodingStandard.NamingConventions.Trait.Suffix
trait TraitSetup
{
    /** @var non-empty-array<string, string> */
    protected $variables = [
        'hostname' => 'TESTS_LAMINAS_DB_MYSQL_ADAPTER_HOSTNAME',
        'username' => 'TESTS_LAMINAS_DB_MYSQL_ADAPTER_USERNAME',
        'password' => 'TESTS_LAMINAS_DB_MYSQL_ADAPTER_PASSWORD',
        'database' => 'TESTS_LAMINAS_DB_MYSQL_ADAPTER_DATABASE',
    ];

    /** @var non-empty-array<string, string> */
    protected $optional = [
        'port' => 'TESTS_LAMINAS_DB_MYSQL_ADAPTER_PORT',
    ];

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        // todo: remove this since we require mysqli extension via composer require-dev
        if (! extension_loaded('mysqli')) {
            $this->fail('The phpunit group integration-mysqli was enabled, but the extension is not loaded.');
        }

        foreach ($this->variables as $name => $value) {
            if (!is_string(getenv($value))) {
                $this->markTestSkipped("Missing required variable $value from phpunit.xml for this integration test");
            }
            if (is_string(getenv($value))) {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->variables[$name] = getenv($value);
            }
        }

        foreach ($this->optional as $name => $value) {
            if (is_string(getenv($value))) {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->variables[$name] = getenv($value);
            }
        }
    }

    protected function getDriverFactory(): callable
    {
        return function(array $dbConfig): Object {
            $options = [];
            if (isset($dbConfig['options'])) {
                $options = (array) $dbConfig['options'];
                unset($dbConfig['options']);
            }
            if (isset($dbConfig['driver'])) {
                /** @psalm-suppress MixedMethodCall */
                return new $dbConfig['driver']($dbConfig, null, null, $options);
            }
            return new Driver($dbConfig, null, null, $options);
        };
    }
}
