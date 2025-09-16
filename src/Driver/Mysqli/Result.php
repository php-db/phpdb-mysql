final <?php

declare(strict_types=1);

namespace PhpDb\Adapter\Mysql\Driver\Mysqli;

use Iterator;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Exception;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use ReturnTypeWillChange;

use function array_fill;
use function call_user_func_array;
use function count;

class Result implements Iterator, ResultInterface
{
    protected mysqli|mysqli_result|mysqli_stmt $resource;

    protected bool $isBuffered;

    protected int $position = 0;

    protected int $numberOfRows = -1;

    /**
     * Is the current() operation already complete for this pointer position?
     */
    protected bool $currentComplete = false;

    protected bool $nextComplete = false;

    /** @var mixed */
    protected $currentData;

    protected array $statementBindValues = ['keys' => null, 'values' => []];

    protected mixed $generatedValue;

    /**
     * Initialize
     *
     * @throws Exception\InvalidArgumentException
     * psalm-suppress PossiblyUnusedMethod
     */
    public function initialize(
        mysqli|mysqli_result|mysqli_stmt $resource,
        mixed $generatedValue,
        ?bool $isBuffered = null
    ): ResultInterface {
        if (
            ! $resource instanceof mysqli
            && ! $resource instanceof mysqli_result
            && ! $resource instanceof mysqli_stmt
        ) {
            throw new Exception\InvalidArgumentException('Invalid resource provided.');
        }

        /**
         * todo: examine this closely to see if this is the correct behavior
         */
        if ($isBuffered !== null) {
            $this->isBuffered = $isBuffered;
        } else {
            if (
                $resource instanceof mysqli || $resource instanceof mysqli_result
                || $resource instanceof mysqli_stmt && $resource->num_rows !== 0
            ) {
                $this->isBuffered = true;
            }
        }

        $this->resource       = $resource;
        $this->generatedValue = $generatedValue;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function buffer(): void
    {
        if ($this->resource instanceof mysqli_stmt && $this->isBuffered !== true) {
            if ($this->position > 0) {
                throw new Exception\RuntimeException('Cannot buffer a result set that has started iteration.');
            }
            $this->resource->store_result();
            $this->isBuffered = true;
        }
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
    public function getResource(): mysqli|mysqli_result|mysqli_stmt
    {
        return $this->resource;
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
     * {@inheritDoc}
     *
     * @return int|numeric-string
     *
     * @psalm-return int<-1, max>|numeric-string
     */
    #[Override]
    public function getAffectedRows(): int|string
    {
        if ($this->resource instanceof mysqli || $this->resource instanceof mysqli_stmt) {
            return $this->resource->affected_rows;
        }

        return $this->resource->num_rows;
    }

    /**
     * Current
     *
     * @return mixed
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
        } else {
            $this->loadFromMysqliResult();
            return $this->currentData;
        }
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
        // build the default reference based bind structure, if it does not already exist
        if ($this->statementBindValues['keys'] === null) {
            $this->statementBindValues['keys'] = [];
            $resultResource                    = $this->resource->result_metadata();
            foreach ($resultResource->fetch_fields() as $col) {
                $this->statementBindValues['keys'][] = $col->name;
            }
            $this->statementBindValues['values'] = array_fill(0, count($this->statementBindValues['keys']), null);
            foreach ($this->statementBindValues['values'] as $i => &$f) {
                $refs[$i] = &$f;
            }
            call_user_func_array([$this->resource, 'bind_result'], $this->statementBindValues['values']);
        }

        if (($r = $this->resource->fetch()) === null) {
            if (! $this->isBuffered) {
                $this->resource->close();
            }
            return false;
        } elseif ($r === false) {
            throw new Exception\RuntimeException($this->resource->error);
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
     */
    protected function loadFromMysqliResult(): bool
    {
        $this->currentData = null;

        if (($data = $this->resource->fetch_assoc()) === null) {
            return false;
        }

        $this->position++;
        $this->currentData     = $data;
        $this->currentComplete = true;
        $this->nextComplete    = true;
        $this->position++;
        return true;
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

        if ($this->nextComplete === false) {
            $this->position++;
        }

        $this->nextComplete = false;
    }

    /**
     * Key
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function key()
    {
        return $this->position;
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
        if (0 !== $this->position && false === $this->isBuffered) {
            throw new Exception\RuntimeException('Unbuffered results cannot be rewound for multiple iterations');
        }

        $this->resource->data_seek(0); // works for both mysqli_result & mysqli_stmt
        $this->currentComplete = false;
        $this->position        = 0;
    }

    /**
     * Valid
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
     * Count
     *
     * @throws Exception\RuntimeException
     * @return int
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function count()
    {
        if ($this->isBuffered === false) {
            throw new Exception\RuntimeException('Row count is not available in unbuffered result sets.');
        }
        return $this->resource->num_rows;
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
     *
     * @return mixed|null
     */
    #[Override]
    public function getGeneratedValue()
    {
        return $this->generatedValue;
    }
}
