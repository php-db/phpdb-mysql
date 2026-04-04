<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use mysqli;
use mysqli_stmt;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverAwareInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

use function array_intersect_key;
use function array_merge;
use function extension_loaded;
use function is_string;

final class Driver implements DriverInterface, ProfilerAwareInterface
{
    protected ?ProfilerInterface $profiler = null;

    /** @var array */
    protected $options = [
        'buffer_results' => false,
    ];

    public function __construct(
        protected readonly ConnectionInterface&Connection $connection,
        protected readonly StatementInterface&Statement $statementPrototype = new Statement(),
        protected readonly ResultInterface&Result $resultPrototype = new Result(),
        array $options = []
    ) {
        $this->checkEnvironment();

        $options = array_intersect_key(array_merge($this->options, $options), $this->options);

        if ($this->connection instanceof DriverAwareInterface) {
            $this->connection->setDriver($this);
        }

        if ($this->statementPrototype instanceof DriverAwareInterface) {
            $this->statementPrototype->setDriver($this);
        }
    }

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
     * Get statement prototype
     */
    public function getStatementPrototype(): StatementInterface&Statement
    {
        return $this->statementPrototype;
    }

    public function getResultPrototype(): ResultInterface&Result
    {
        return $this->resultPrototype;
    }

    public function checkEnvironment(): bool
    {
        if (! extension_loaded('mysqli')) {
            throw new Exception\RuntimeException(
                'The Mysqli extension is required for this adapter but the extension is not loaded'
            );
        }
        return true;
    }

    public function getConnection(): ConnectionInterface&Connection
    {
        return $this->connection;
    }

    /**
     * Create statement
     *
     * @param mysqli|mysqli_stmt|string $sqlOrResource
     */
    public function createStatement($sqlOrResource = null): StatementInterface&Statement
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
            /** @var mysqli $resource */
            $resource = $this->connection->getResource();
            $statement->initialize($resource);
        }
        return $statement;
    }

    /**
     * Create result
     *
     * @param mysqli|mysqli_result|mysqli_stmt $resource
     */
    public function createResult($resource, ?bool $isBuffered = null): ResultInterface&Result
    {
        /** @var Result $result */
        $result = clone $this->resultPrototype;
        $result->initialize($resource, $this->connection->getLastGeneratedValue(), $isBuffered);
        return $result;
    }

    /**
     * Get prepare type
     */
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }

    /**
     * Format parameter name
     */
    public function formatParameterName(string $name, ?string $type = null): string
    {
        return '?';
    }

    /**
     * Get last generated value
     */
    public function getLastGeneratedValue(): int|string|null|false
    {
        return $this->getConnection()->getLastGeneratedValue();
    }
}
