<?php

declare(strict_types=1);

namespace PhpDb\Mysql;

use Iterator;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Exception;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use ReturnTypeWillChange;

use function array_fill;
use function call_user_func_array;
use function count;

// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
// @mago-expect lint:too-many-methods
/** @implements Iterator<int, array<array-key, mixed>|null> */
final class Result implements Iterator, ResultInterface
{
    protected mysqli|mysqli_result|mysqli_stmt $resource;

    protected ?bool $isBuffered = null;

    protected int $position = 0;

    protected int $numberOfRows = -1;

    /**
     * Is the current() operation already complete for this pointer position?
     */
    protected bool $currentComplete = false;

    protected bool $nextComplete = false;

    /** @var array<array-key, mixed>|null */
    protected ?array $currentData = null;

    /** @var array{keys: string[]|null, values: array<int, mixed>} */
    protected array $statementBindValues = ['keys' => null, 'values' => []];

    protected string|int|false|null $generatedValue = null;

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function buffer(): void
    {
        if ($this->resource instanceof mysqli_stmt && ! $this->isBuffered) {
            if ($this->position > 0) {
                throw new Exception\RuntimeException('Cannot buffer a result set that has started iteration.');
            }
            $this->resource->store_result();
            $this->isBuffered = true;
        }
    }

    /**
     * Count
     *
     * @throws Exception\RuntimeException
     * @return int
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function count()
    {
        if (! $this->isBuffered) {
            throw new Exception\RuntimeException('Row count is not available in unbuffered result sets.');
        }

        if (! $this->resource instanceof mysqli_result && ! $this->resource instanceof mysqli_stmt) {
            throw new Exception\RuntimeException('Cannot count rows in a result that is not a query result');
        }

        return (int) $this->resource->num_rows;
    }

    /**
     * Current
     *
     * @throws Exception\ExceptionInterface
     *
     * @return array<array-key, mixed>|null
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function current()
    {
        if ($this->currentComplete) {
            return $this->currentData;
        }

        if ($this->resource instanceof mysqli_stmt) {
            $this->loadDataFromMysqliStatement();
            return $this->currentData;
        }

        $this->loadFromMysqliResult();
        return $this->currentData;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getAffectedRows(): int
    {
        if ($this->resource instanceof mysqli || $this->resource instanceof mysqli_stmt) {
            return (int) $this->resource->affected_rows;
        }

        return (int) $this->resource->num_rows;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getFieldCount(): int
    {
        return $this->resource->field_count;
    }

    /**
     * Get generated value
     */
    #[Override]
    public function getGeneratedValue(): string|int|false|null
    {
        return $this->generatedValue;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException When isQueryResult() is false.
     * @throws \Exception If the seeded result set rejects this result as its data source.
     */
    #[Override]
    public function getQueryResult(?ResultSetInterface $resultPrototype = null): ResultSetInterface
    {
        if (! $this->isQueryResult()) {
            throw new Exception\RuntimeException(
                'Cannot produce a query result set from a result that is not a query result;'
                    . ' check isQueryResult() first',
            );
        }

        $resultPrototype ??= new ResultSet();
        $resultSet       = clone $resultPrototype;
        $resultSet->initialize($this);

        return $resultSet;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getResource(): mysqli|mysqli_result|mysqli_stmt
    {
        return $this->resource;
    }

    /**
     * Initialize
     *
     * @throws Exception\InvalidArgumentException
     * psalm-suppress PossiblyUnusedMethod
     */
    public function initialize(
        mysqli|mysqli_result|mysqli_stmt $resource,
        string|int|false|null $generatedValue,
        ?bool $isBuffered = null,
    ): ResultInterface {
        /**
         * todo(@tyrsson): examine this closely to see if this is the correct behavior
         */
        $this->isBuffered = match (true) {
            null !== $isBuffered => $isBuffered,
            $resource instanceof mysqli
                || $resource instanceof mysqli_result
                || 0 !== $resource->num_rows
                => true,
            default => $this->isBuffered,
        };

        $this->resource       = $resource;
        $this->generatedValue = $generatedValue;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function isBuffered(): ?bool
    {
        return $this->isBuffered;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function isQueryResult(): bool
    {
        return $this->resource->field_count > 0;
    }

    /**
     * Key
     *
     * @return int
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function key()
    {
        return $this->position;
    }

    /**
     * Next
     *
     * @return void
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function next()
    {
        $this->currentComplete = false;

        if (! $this->nextComplete) {
            $this->position++;
        }

        $this->nextComplete = false;
    }

    /**
     * Rewind
     *
     * @throws Exception\RuntimeException
     * @return void
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function rewind()
    {
        if (0 !== $this->position && ! $this->isBuffered) {
            throw new Exception\RuntimeException('Unbuffered results cannot be rewound for multiple iterations');
        }

        if (! $this->resource instanceof mysqli_result && ! $this->resource instanceof mysqli_stmt) {
            throw new Exception\RuntimeException('Cannot rewind a result that is not a query result');
        }

        $this->resource->data_seek(0); // works for both mysqli_result & mysqli_stmt
        $this->currentComplete = false;
        $this->position        = 0;
    }

    /**
     * Valid
     *
     * @throws Exception\ExceptionInterface
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function valid()
    {
        if ($this->currentComplete) {
            return true;
        }

        if ($this->resource instanceof mysqli_stmt) {
            return $this->loadDataFromMysqliStatement();
        }

        return $this->loadFromMysqliResult();
    }

    /**
     * Mysqli's binding and returning of statement values
     *
     * Mysqli requires you to bind variables to the extension in order to
     * get data out.  These values have to be references:
     *
     * @see http://php.net/manual/en/mysqli-stmt.bind-result.php
     *
     * @throws Exception\RuntimeException
     */
    protected function loadDataFromMysqliStatement(): bool
    {
        /** @var mysqli_stmt $statement Guaranteed by the instanceof dispatch in current() and valid(). */
        $statement = $this->resource;

        // build the default reference based bind structure, if it does not already exist
        if (null === $this->statementBindValues['keys']) {
            $this->statementBindValues['keys'] = [];
            $resultResource                    = $statement->result_metadata();
            if (false === $resultResource) {
                return $resultResource;
            }

            foreach ($resultResource->fetch_fields() as $col) {
                /** @var object{name: string} $col */
                $this->statementBindValues['keys'][] = $col->name;
            }
            $this->statementBindValues['values'] = array_fill(
                0,
                count($this->statementBindValues['keys']),
                value: null,
            );
            $refs = [];
            foreach ($this->statementBindValues['values'] as $i => &$f) {
                $refs[$i] = &$f;
            }
            call_user_func_array([$statement, 'bind_result'], $this->statementBindValues['values']);
        }

        if (($r = $statement->fetch()) === null) {
            if (! $this->isBuffered) {
                $statement->close();
            }
            return false;
        }

        if (! $r) {
            throw new Exception\RuntimeException($statement->error);
        }

        // dereference
        for ($i = 0, $count = count($this->statementBindValues['keys']); $i < $count; $i++) {
            $this->currentData[$this->statementBindValues['keys'][$i]] = $this->statementBindValues['values'][$i];
        }
        $this->currentComplete = true;
        $this->nextComplete    = true;
        $this->position++;
        return true;
    }

    /**
     * Load from mysqli result
     *
     * @throws Exception\RuntimeException
     */
    protected function loadFromMysqliResult(): bool
    {
        $this->currentData = null;

        if (! $this->resource instanceof mysqli_result) {
            throw new Exception\RuntimeException('Cannot fetch from a result that is not a mysqli_result');
        }

        /** @var array<array-key, mixed>|null $data */
        $data = $this->resource->fetch_assoc();

        if (null === $data) {
            return false;
        }

        $this->position++;
        $this->currentData     = $data;
        $this->currentComplete = true;
        $this->nextComplete    = true;
        $this->position++;
        return true;
    }
}
