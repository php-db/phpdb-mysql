<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use mysqli;
use mysqli_stmt;
use Override;
use PhpDb\Adapter\Driver\DriverAwareInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Profiler\ProfilerAwareInterface;
use PhpDb\Adapter\Profiler\ProfilerInterface;
use PhpDb\Adapter\StatementContainerInterface;

use function array_unshift;
use function call_user_func_array;
use function is_array;
use function sprintf;

final class Statement implements StatementInterface, DriverAwareInterface, ProfilerAwareInterface
{
    protected mysqli $mysqli;

    protected Driver $driver;

    protected ?ProfilerInterface $profiler = null;

    protected string $sql = '';

    protected mysqli_stmt $resource;

    protected bool $isPrepared = false;

    public function __construct(
        protected ParameterContainer $parameterContainer = new ParameterContainer(),
        protected bool $bufferResults = false,
    ) {}

    /**
     * Execute
     *
     * @param array<array-key, mixed>|ParameterContainer|null $parameters
     *
     * @throws Exception\ExceptionInterface
     */
    #[Override]
    public function execute(ParameterContainer|array|null $parameters = null): ?ResultInterface
    {
        if (! $this->isPrepared) {
            $this->prepare();
        }

        /** START Standard ParameterContainer Merging Block */
        if ($parameters instanceof ParameterContainer) {
            $this->parameterContainer = $parameters;
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

        if (! $return) {
            throw new Exception\RuntimeException($this->resource->error);
        }

        $buffered = false;
        if ($this->bufferResults) {
            $this->resource->store_result();
            $this->isPrepared = false;
            $buffered         = true;
        }

        return $this->driver->createResult($this->resource, $buffered);
    }

    #[Override]
    public function getParameterContainer(): ?ParameterContainer
    {
        return $this->parameterContainer;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * @phpstan-ignore method.childReturnType
     *
     * @return mysqli_stmt
     */
    // @mago-expect analysis:incompatible-return-type - StatementInterface::getResource() declares no
    // native return type, only a legacy `resource|false|null` docblock; this class's $resource is
    // always a genuine mysqli_stmt, so the narrower native return type here is a valid PHP covariant
    // override, not a real incompatibility.
    #[Override]
    public function getResource(): mysqli_stmt
    {
        return $this->resource;
    }

    #[Override]
    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function initialize(mysqli $mysqli): static
    {
        $this->mysqli = $mysqli;
        return $this;
    }

    #[Override]
    public function isPrepared(): bool
    {
        return $this->isPrepared;
    }

    /**
     * @throws Exception\ExceptionInterface
     */
    #[Override]
    public function prepare(?string $sql = null): StatementInterface
    {
        if ($this->isPrepared) {
            throw new Exception\RuntimeException('This statement has already been prepared');
        }

        $sql = null === $sql || '' === $sql ? $this->sql : $sql;

        $resource = $this->mysqli->prepare($sql);
        if (! $resource instanceof mysqli_stmt) {
            throw new Exception\InvalidQueryException(
                "Statement couldn't be produced with sql: {$sql}",
                $this->mysqli->errno,
                new Exception\ErrorException($this->mysqli->error, $this->mysqli->errno),
            );
        }

        $this->resource   = $resource;
        $this->isPrepared = true;
        return $this;
    }

    #[Override]
    public function setDriver(DriverInterface $driver): DriverAwareInterface
    {
        if (! $driver instanceof Driver) {
            throw new Exception\InvalidArgumentException(sprintf('Driver must be an instance of %s', Driver::class));
        }

        $this->driver = $driver;
        return $this;
    }

    #[Override]
    public function setParameterContainer(
        ParameterContainer $parameterContainer,
    ): StatementContainerInterface {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    #[Override]
    public function setProfiler(ProfilerInterface $profiler): ProfilerAwareInterface
    {
        $this->profiler = $profiler;
        return $this;
    }

    public function setResource(mysqli_stmt $mysqliStatement): StatementInterface
    {
        $this->resource   = $mysqliStatement;
        $this->isPrepared = true;
        return $this;
    }

    #[Override]
    public function setSql(?string $sql): StatementContainerInterface
    {
        $this->sql = $sql ?? '';
        return $this;
    }

    /**
     * Bind parameters from container
     */
    protected function bindParametersFromContainer(): void
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
            }
            $args[] = &$value;
        }

        if ($args) {
            array_unshift($args, $type);
            call_user_func_array([$this->resource, 'bind_param'], $args);
        }
    }
}
