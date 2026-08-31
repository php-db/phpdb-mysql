<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

final class CreateTableDecorator extends CreateTable implements PlatformDecoratorInterface
{
    use ColumnOptionTrait;

    // @mago-expect analysis:write-only-property - read by the inherited AbstractSql::$subject handling
    // (get_object_vars($this->subject)), since CreateTable extends AbstractSql
    protected SqlInterface|PreparableSqlInterface|null $subject = null;

    #[Override]
    public function setSubject(
        PreparableSqlInterface|SqlInterface|null $subject,
    ): PlatformDecoratorInterface {
        $this->subject = $subject;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    protected function processColumns(?PlatformInterface $adapterPlatform = null): ?array
    {
        if (! $this->columns) {
            return null;
        }

        if (null === $adapterPlatform) {
            throw new Exception\RuntimeException('Cannot build column SQL without a platform.');
        }

        $sqls = [];

        /** @var array<array-key, ColumnInterface> $columns */
        $columns = $this->columns;

        foreach ($columns as $i => $column) {
            /** @var array<string, mixed> $options */
            $options = $column->getOptions();

            $sqls[$i] = $this->processColumnOptions(
                $this->processExpression($column, $adapterPlatform),
                $options,
                $adapterPlatform,
            );
        }

        return [$sqls];
    }
}
