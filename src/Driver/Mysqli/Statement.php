<?php

declare(strict_types=1);

namespace Laminas\Db\Adapter\Mysql\Driver\Mysqli;

use Laminas\Db\Adapter\Driver\DriverAwareInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Profiler\ProfilerAwareInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Laminas\Db\Adapter\StatementContainerInterface;
use Laminas\Db\Adapter\Exception;
use mysqli_stmt;

use function array_unshift;
use function call_user_func_array;
use function is_array;

class Statement implements StatementInterface, DriverAwareInterface, ProfilerAwareInterface
{
    protected \mysqli $mysqli;

    protected Mysqli $driver;

    protected ?ProfilerInterface $profiler = null;

    protected string $sql = '';

    protected ?ParameterContainer $parameterContainer = null;

    /** @var mysqli_stmt */
    protected $resource;

    protected $isPrepared = false;

    protected $bufferResults = false;

    /**
     * @param bool $bufferResults
     */
    public function __construct($bufferResults = false)
    {
        $this->bufferResults = (bool) $bufferResults;
    }

    /**
     * Set driver
     *
     * @return $this Provides a fluent interface
     */
    public function setDriver(DriverInterface $driver): DriverAwareInterface
    {
        $this->driver = $driver;
        return $this;
    }

    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        return $this;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * Initialize
     *
     * @return $this Provides a fluent interface
     */
    public function initialize(\mysqli $mysqli): static
    {
        $this->mysqli = $mysqli;
        return $this;
    }

    /**
     * Set sql
     *
     * @param  string $sql
     * @return $this Provides a fluent interface
     */
    public function setSql($sql): StatementContainerInterface
    {
        $this->sql = $sql;
        return $this;
    }

    /** Set Parameter container */
    public function setParameterContainer(ParameterContainer $parameterContainer): StatementContainerInterface
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    /**
     * Get resource
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set resource
     *
     * @return $this Provides a fluent interface
     */
    public function setResource(mysqli_stmt $mysqliStatement)
    {
        $this->resource   = $mysqliStatement;
        $this->isPrepared = true;
        return $this;
    }

    /**
     * Get sql
     *
     * @return string|null
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /** Get parameter count */
    public function getParameterContainer(): ?ParameterContainer
    {
        return $this->parameterContainer;
    }

    /** Is prepared */
    public function isPrepared(): bool
    {
        return $this->isPrepared;
    }

    public function prepare(?string $sql = null): StatementInterface
    {
        if ($this->isPrepared) {
            throw new Exception\RuntimeException('This statement has already been prepared');
        }

        $sql = $sql ?: $this->sql;

        $this->resource = $this->mysqli->prepare($sql);
        if (! $this->resource instanceof mysqli_stmt) {
            throw new Exception\InvalidQueryException(
                'Statement couldn\'t be produced with sql: ' . $sql,
                $this->mysqli->errno,
                new Exception\ErrorException($this->mysqli->error, $this->mysqli->errno)
            );
        }

        $this->isPrepared = true;
        return $this;
    }

    /**
     * Execute
     *
     * @throws Exception\RuntimeException
     */
    public function execute(null|array|ParameterContainer $parameters = null): ?ResultInterface
    {
        if (! $this->isPrepared) {
            $this->prepare();
        }

        /** START Standard ParameterContainer Merging Block */
        if (! $this->parameterContainer instanceof ParameterContainer) {
            if ($parameters instanceof ParameterContainer) {
                $this->parameterContainer = $parameters;
                $parameters               = null;
            } else {
                $this->parameterContainer = new ParameterContainer();
            }
        }

        if (is_array($parameters)) {
            $this->parameterContainer->setFromArray($parameters);
        }

        if ($this->parameterContainer->count() > 0) {
            $this->bindParametersFromContainer();
        }
        /** END Standard ParameterContainer Merging Block */

        $this->profiler?->profilerStart($this);

        $return = $this->resource->execute();

        $this->profiler?->profilerFinish();

        if ($return === false) {
            throw new Exception\RuntimeException($this->resource->error);
        }

        if ($this->bufferResults === true) {
            $this->resource->store_result();
            $this->isPrepared = false;
            $buffered         = true;
        } else {
            $buffered = false;
        }

        return $this->driver->createResult($this->resource, $buffered);
    }

    /**
     * Bind parameters from container
     *
     * @return void
     */
    protected function bindParametersFromContainer()
    {
        $parameters = $this->parameterContainer->getNamedArray();
        $type       = '';
        $args       = [];

        foreach ($parameters as $name => &$value) {
            if ($this->parameterContainer->offsetHasErrata($name)) {
                switch ($this->parameterContainer->offsetGetErrata($name)) {
                    case ParameterContainer::TYPE_DOUBLE:
                        $type .= 'd';
                        break;
                    case ParameterContainer::TYPE_NULL:
                        $value = null; // as per @see http://www.php.net/manual/en/mysqli-stmt.bind-param.php#96148
                    case ParameterContainer::TYPE_INTEGER:
                        $type .= 'i';
                        break;
                    case ParameterContainer::TYPE_STRING:
                    default:
                        $type .= 's';
                        break;
                }
            } else {
                $type .= 's';
            }
            $args[] = &$value;
        }

        if ($args) {
            array_unshift($args, $type);
            call_user_func_array([$this->resource, 'bind_param'], $args);
        }
    }
}
