<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

final class AlterTableDecorator extends AlterTable implements PlatformDecoratorInterface
{
    use ColumnOptionTrait;

    // @mago-expect analysis:write-only-property - read by the inherited AbstractSql::$subject handling
    // (get_object_vars($this->subject)), since AlterTable extends AbstractSql
    protected SqlInterface|PreparableSqlInterface|null $subject = null;

    #[Override]
    public function setSubject(
        SqlInterface|PreparableSqlInterface|null $subject,
    ): PlatformDecoratorInterface {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return array<int, array<int|string, string>>
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    protected function processAddColumns(?PlatformInterface $adapterPlatform = null): array
    {
        if (null === $adapterPlatform) {
            throw new Exception\RuntimeException('Cannot build column SQL without a platform.');
        }

        $sqls = [];

        foreach ($this->addColumns as $i => $column) {
            $sqls[$i] = $this->processColumnOptions(
                $this->processExpression($column, $adapterPlatform),
                $column->getOptions(),
                $adapterPlatform,
                $this->resolveAfterOption(...),
            );
        }

        return [$sqls];
    }

    /**
     * @return array<int, array<int|string, string>>
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    protected function processChangeColumns(?PlatformInterface $adapterPlatform = null): array
    {
        if (null === $adapterPlatform) {
            throw new Exception\RuntimeException('Cannot build column SQL without a platform.');
        }

        $sqls = [];

        foreach ($this->changeColumns as $name => $column) {
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                $this->processColumnOptions(
                    $this->processExpression($column, $adapterPlatform),
                    $column->getOptions(),
                    $adapterPlatform,
                ),
            ];
        }

        return [$sqls];
    }

    /**
     * @return array{string, int}|null
     */
    private function resolveAfterOption(string $option, mixed $value, ?PlatformInterface $platform): ?array
    {
        return $option === 'after'
            ? [' AFTER ' . $platform->quoteIdentifier($value), 2]
            : null;
    }
}
