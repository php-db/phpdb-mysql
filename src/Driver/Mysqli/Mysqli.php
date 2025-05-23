<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Driver\Mysqli;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverAwareInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Exception;
use Laminas\Db\Adapter\Profiler\ProfilerAwareInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Laminas\Db\Adapter\Mysql\DatabasePlatformNameTrait;
use mysqli_stmt;

use function array_intersect_key;
use function array_merge;
use function extension_loaded;
use function is_string;

class Mysqli implements DriverInterface, ProfilerAwareInterface
{
    use DatabasePlatformNameTrait;

    protected ?ProfilerInterface $profiler = null;

    /** @var array */
    protected $options = [
        'buffer_results' => false,
    ];

    public function __construct(
        protected ConnectionInterface|\mysqli|array $connection,
        protected ?StatementInterface $statementPrototype = null,
        protected ?ResultInterface $resultPrototype = null,
        array $options = []
    ) {
        if (! $connection instanceof Connection) {
            $connection = new Connection($connection);
        }

        $options = array_intersect_key(array_merge($this->options, $options), $this->options);

        $this->registerConnection($connection);
        $this->registerStatementPrototype($statementPrototype ?: new Statement($options['buffer_results']));
        $this->registerResultPrototype($resultPrototype ?: new Result());
    }

    /**
     * @return $this Provides a fluent interface
     */
    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        if ($this->connection instanceof ProfilerAwareInterface) {
            $this->connection->setProfiler($profiler);
        }
        if ($this->statementPrototype instanceof ProfilerAwareInterface) {
            $this->statementPrototype->setProfiler($profiler);
        }
        return $this;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * Register connection
     *
     * @return $this Provides a fluent interface
     */
    public function registerConnection(ConnectionInterface $connection): DriverInterface
    {
        $this->connection = $connection;
        if ($this->connection instanceof DriverAwareInterface) {
            $this->connection->setDriver($this);
        }
        return $this;
    }

    /**
     * Register statement prototype
     */
    public function registerStatementPrototype(StatementInterface $statementPrototype): static
    {
        $this->statementPrototype = $statementPrototype;
        if ($this->statementPrototype instanceof DriverAwareInterface) {
            $this->statementPrototype->setDriver($this);
        }
        return $this;
    }

    /**
     * Get statement prototype
     *
     * @return null|Statement
     */
    public function getStatementPrototype()
    {
        return $this->statementPrototype;
    }

    /**
     * Register result prototype
     */
    public function registerResultPrototype(Result $resultPrototype)
    {
        $this->resultPrototype = $resultPrototype;
    }

    /**
     * @return null|Result
     */
    public function getResultPrototype()
    {
        return $this->resultPrototype;
    }

    /**
     * Check environment
     *
     * @throws Exception\RuntimeException
     * @return void
     */
    public function checkEnvironment(): bool
    {
        if (! extension_loaded('mysqli')) {
            throw new Exception\RuntimeException(
                'The Mysqli extension is required for this adapter but the extension is not loaded'
            );
        }
        return true;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Create statement
     *
     * @param string $sqlOrResource
     * @return Statement
     */
    public function createStatement($sqlOrResource = null): StatementInterface
    {
        /**
         * @todo Resource tracking
         * if (is_resource($sqlOrResource) && !in_array($sqlOrResource, $this->resources, true)) {
         *   $this->resources[] = $sqlOrResource;
         *}
         */

        $statement = clone $this->statementPrototype;
        if ($sqlOrResource instanceof mysqli_stmt) {
            $statement->setResource($sqlOrResource);
        } else {
            if (is_string($sqlOrResource)) {
                $statement->setSql($sqlOrResource);
            }
            if (! $this->connection->isConnected()) {
                $this->connection->connect();
            }
            $statement->initialize($this->connection->getResource());
        }
        return $statement;
    }

    /**
     * Create result
     *
     * @param resource $resource
     * @param null|bool $isBuffered
     * @return Result
     */
    public function createResult($resource, $isBuffered = null): ResultInterface
    {
        $result = clone $this->resultPrototype;
        $result->initialize($resource, $this->connection->getLastGeneratedValue(), $isBuffered);
        return $result;
    }

    /**
     * Get prepare type
     *
     * @return string
     */
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }

    /**
     * Format parameter name
     *
     * @param string $name
     * @param mixed  $type
     * @return string
     */
    public function formatParameterName($name, $type = null): string
    {
        return '?';
    }

    /**
     * Get last generated value
     *
     * @return mixed
     */
    public function getLastGeneratedValue(): int|string|null|false
    {
        return $this->getConnection()->getLastGeneratedValue();
    }
}
