<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Sql\Ddl;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
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
     */
    #[Override]
    protected function processAddColumns(?PlatformInterface $adapterPlatform = null): array
    {
        /** @var PlatformInterface $platform */
        $platform = $adapterPlatform;

        $sqls = [];

        /** @var array<array-key, ColumnInterface> $addColumns */
        $addColumns = $this->addColumns;

        foreach ($addColumns as $i => $column) {
            /** @var array<string, mixed> $options */
            $options = $column->getOptions();

            $sqls[$i] = $this->processColumnOptions(
                $this->processExpression($column, $platform),
                $options,
                $platform,
                $this->resolveAfterOption(...),
            );
        }

        return [$sqls];
    }

    /**
     * @return array{0: list<array{string, string}>}
     */
    #[Override]
    protected function processChangeColumns(?PlatformInterface $adapterPlatform = null): array
    {
        /** @var PlatformInterface $platform */
        $platform = $adapterPlatform;

        $sqls = [];

        /** @var array<string, ColumnInterface> $changeColumns */
        $changeColumns = $this->changeColumns;

        foreach ($changeColumns as $name => $column) {
            /** @var array<string, mixed> $options */
            $options = $column->getOptions();

            $sqls[] = [
                $platform->quoteIdentifier($name),
                $this->processColumnOptions(
                    $this->processExpression($column, $platform),
                    $options,
                    $platform,
                ),
            ];
        }

        return [$sqls];
    }

    private function resolveAfterOption(string $option, mixed $value, PlatformInterface $platform): ?string
    {
        if ('after' !== $option) {
            return null;
        }

        return $platform->quoteIdentifier((string) $value);
    }
}
