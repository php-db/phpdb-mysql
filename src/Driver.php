<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use mysqli;
use mysqli_stmt;
use Override;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;

use function array_intersect_key;
use function extension_loaded;
use function is_string;

final class Driver implements DriverInterface, ProfilerAwareInterface
{
    protected ?ProfilerInterface $profiler = null;

    /** @var array */
    protected $options = [
        'buffer_results' => false,
    ];

    /**
     * @param array<string, mixed> $options
     *
     * @throws \PhpDb\Exception\ExceptionInterface
     */
    public function __construct(
        protected readonly ConnectionInterface&Connection $connection,
        protected readonly StatementInterface&Statement $statementPrototype = new Statement(),
        protected readonly ResultInterface&Result $resultPrototype = new Result(),
        array $options = [],
    ) {
        $this->checkEnvironment();

        $options = array_intersect_key([...$this->options, ...$options], $this->options);

        $this->connection->setDriver($this);
        $this->statementPrototype->setDriver($this);
    }

    #[Override]
    public function checkEnvironment(): bool
    {
        // @codeCoverageIgnoreStart
        if (! extension_loaded('mysqli')) {
            throw new Exception\RuntimeException(
                'The Mysqli extension is required for this adapter but the extension is not loaded',
            );
        }
        // @codeCoverageIgnoreEnd
        return true;
    }

    /**
     * Create result
     *
     * @param mysqli|mysqli_result|mysqli_stmt $resource
     */
    #[Override]
    public function createResult($resource, ?bool $isBuffered = null): ResultInterface&Result
    {
        $result = clone $this->resultPrototype;
        $result->initialize($resource, $this->connection->getLastGeneratedValue(), $isBuffered);
        return $result;
    }

    /**
     * Create statement
     *
     * @param mysqli|mysqli_stmt|string $sqlOrResource
     *
     * @throws Exception\ExceptionInterface
     */
    #[Override]
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

            return $statement;
        }

        if (is_string($sqlOrResource)) {
            $statement->setSql($sqlOrResource);
        }
        if (! $this->connection->isConnected()) {
            $this->connection->connect();
        }
        /** @var mysqli $resource */
        $resource = $this->connection->getResource();
        $statement->initialize($resource);
        return $statement;
    }

    /**
     * Format parameter name
     */
    #[Override]
    public function formatParameterName(string $name, ?string $type = null): string
    {
        return '?';
    }

    #[Override]
    public function getConnection(): ConnectionInterface&Connection
    {
        return $this->connection;
    }

    /**
     * Get last generated value
     */
    #[Override]
    public function getLastGeneratedValue(): int|string|false|null
    {
        return $this->getConnection()->getLastGeneratedValue();
    }

    /**
     * Get prepare type
     */
    #[Override]
    public function getPrepareType(): string
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    public function getResultPrototype(): ResultInterface&Result
    {
        return $this->resultPrototype;
    }

    /**
     * Get statement prototype
     */
    public function getStatementPrototype(): StatementInterface&Statement
    {
        return $this->statementPrototype;
    }

    #[Override]
    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        $this->connection->setProfiler($profiler);
        $this->statementPrototype->setProfiler($profiler);
        return $this;
    }
}
