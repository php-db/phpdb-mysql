<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

final class AlterTableDecorator extends AlterTable implements PlatformDecoratorInterface
{
    use ColumnOptionTrait;

    protected SqlInterface|PreparableSqlInterface|null $subject;

    public function setSubject(
        SqlInterface|PreparableSqlInterface|null $subject
    ): PlatformDecoratorInterface {
        $this->subject = $subject;

        return $this;
    }

    protected function processAddColumns(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];

        foreach ($this->addColumns as $i => $column) {
            $sqls[$i] = $this->processColumnOptions(
                $this->processExpression($column, $adapterPlatform),
                $column->getOptions(),
                $adapterPlatform,
                $this->resolveAfterOption(...)
            );
        }

        return [$sqls];
    }

    protected function processChangeColumns(?PlatformInterface $adapterPlatform = null): array
    {
        $sqls = [];

        foreach ($this->changeColumns as $name => $column) {
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                $this->processColumnOptions(
                    $this->processExpression($column, $adapterPlatform),
                    $column->getOptions(),
                    $adapterPlatform
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
