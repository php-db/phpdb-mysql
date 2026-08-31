<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

final class CreateTableDecorator extends CreateTable implements PlatformDecoratorInterface
{
    use ColumnOptionTrait;

    protected SqlInterface|PreparableSqlInterface|null $subject;

    public function setSubject(
        PreparableSqlInterface|SqlInterface|null $subject
    ): PlatformDecoratorInterface {
        $this->subject = $subject;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function processColumns(?PlatformInterface $platform = null): ?array
    {
        if (! $this->columns) {
            return null;
        }

        $sqls = [];

        foreach ($this->columns as $i => $column) {
            $sqls[$i] = $this->processColumnOptions(
                $this->processExpression($column, $platform),
                $column->getOptions(),
                $platform
            );
        }

        return [$sqls];
    }
}
