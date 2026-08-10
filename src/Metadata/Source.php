<?php

declare(strict_types=1);

namespace PhpDb\Mysql\Metadata;

use DateTime;
use Exception;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\Source\AbstractSource;
use PhpDb\ResultSet\ResultSetInterface;

use function array_change_key_case;
use function array_walk;
use function implode;
use function preg_match;
use function preg_match_all;
use function str_contains;
use function str_replace;

use const CASE_LOWER;
use const PREG_PATTERN_ORDER;

// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
final class Source extends AbstractSource
{
    // @mago-expect lint:halstead
    #[Override]
    protected function loadColumnData(string $table, string $schema): void
    {
        if (null !== ($this->data['columns'][$schema][$table] ?? null)) {
            return;
        }
        $this->prepareDataHierarchy('columns', $schema, $table);
        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['C', 'ORDINAL_POSITION'],
            ['C', 'COLUMN_DEFAULT'],
            ['C', 'IS_NULLABLE'],
            ['C', 'DATA_TYPE'],
            ['C', 'CHARACTER_MAXIMUM_LENGTH'],
            ['C', 'CHARACTER_OCTET_LENGTH'],
            ['C', 'NUMERIC_PRECISION'],
            ['C', 'NUMERIC_SCALE'],
            ['C', 'COLUMN_NAME'],
            ['C', 'COLUMN_TYPE'],
        ];

        array_walk($isColumns, static function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql =
            'SELECT '
            . implode(', ', $isColumns)
            . ' FROM '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TABLES'])
            . 'T'
            . ' INNER JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'COLUMNS'])
            . 'C'
            . ' ON '
            . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['C', 'TABLE_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['C', 'TABLE_NAME'])
            . ' WHERE '
            . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
            . ' IN (\'BASE TABLE\', \'VIEW\')'
            . ' AND '
            . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
            . '  = '
            . $p->quoteTrustedValue($table);

        $sql .= self::DEFAULT_SCHEMA === $schema
            ? " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} != 'INFORMATION_SCHEMA'"
            : " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} = {$p->quoteTrustedValue($schema)}";

        /** @var ResultSetInterface $results */
        $results = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);
        $columns = [];
        /** @var array{ORDINAL_POSITION: string, COLUMN_DEFAULT: ?string, IS_NULLABLE: string, DATA_TYPE: string, CHARACTER_MAXIMUM_LENGTH: ?string, CHARACTER_OCTET_LENGTH: ?string, NUMERIC_PRECISION: ?string, NUMERIC_SCALE: ?string, COLUMN_NAME: string, COLUMN_TYPE: string} $row */
        foreach ($results->toArray() as $row) {
            $erratas = [];
            $matches = [];
            if (preg_match('/^(?:enum|set)\((.+)\)$/i', $row['COLUMN_TYPE'], $matches)) {
                $permittedValues = $matches[1];
                $permittedValues = preg_match_all(
                    "/\\s*'((?:[^']++|'')*+)'\\s*(?:,|\$)/",
                    $permittedValues,
                    $matches,
                    PREG_PATTERN_ORDER,
                )
                    ? str_replace("''", replace: "'", subject: $matches[1])
                    : [$permittedValues];
                $erratas['permitted_values'] = $permittedValues;
            }
            $columns[$row['COLUMN_NAME']] = [
                'ordinal_position'         => $row['ORDINAL_POSITION'],
                'column_default'           => $row['COLUMN_DEFAULT'],
                'is_nullable'              => 'YES' === $row['IS_NULLABLE'],
                'data_type'                => $row['DATA_TYPE'],
                'character_maximum_length' => $row['CHARACTER_MAXIMUM_LENGTH'],
                'character_octet_length'   => $row['CHARACTER_OCTET_LENGTH'],
                'numeric_precision'        => $row['NUMERIC_PRECISION'],
                'numeric_scale'            => $row['NUMERIC_SCALE'],
                'numeric_unsigned'         => str_contains($row['COLUMN_TYPE'], 'unsigned'),
                'erratas'                  => $erratas,
            ];
        }

        $this->data['columns'][$schema][$table] = $columns;
    }

    // @mago-expect lint:halstead
    #[Override]
    protected function loadConstraintData(string $table, string $schema): void
    {
        // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
        if (null !== ($this->data['constraints'][$schema][$table] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('constraints', $schema, $table);

        $isColumns = [
            ['T',   'TABLE_NAME'],
            ['TC',  'CONSTRAINT_NAME'],
            ['TC',  'CONSTRAINT_TYPE'],
            ['KCU', 'COLUMN_NAME'],
            ['RC',  'MATCH_OPTION'],
            ['RC',  'UPDATE_RULE'],
            ['RC',  'DELETE_RULE'],
            ['KCU', 'REFERENCED_TABLE_SCHEMA'],
            ['KCU', 'REFERENCED_TABLE_NAME'],
            ['KCU', 'REFERENCED_COLUMN_NAME'],
        ];

        $p = $this->adapter->getPlatform();

        array_walk($isColumns, static function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql =
            'SELECT '
            . implode(', ', $isColumns)
            . ' FROM '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TABLES'])
            . ' T'
            . ' INNER JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TABLE_CONSTRAINTS'])
            . ' TC'
            . ' ON '
            . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['TC', 'TABLE_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['TC', 'TABLE_NAME'])
            . ' LEFT JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'KEY_COLUMN_USAGE'])
            . ' KCU'
            . ' ON '
            . $p->quoteIdentifierChain(['TC', 'TABLE_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['KCU', 'TABLE_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['TC', 'TABLE_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['KCU', 'TABLE_NAME'])
            . ' AND '
            . $p->quoteIdentifierChain(['TC', 'CONSTRAINT_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['KCU', 'CONSTRAINT_NAME'])
            . ' LEFT JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'REFERENTIAL_CONSTRAINTS'])
            . ' RC'
            . ' ON '
            . $p->quoteIdentifierChain(['TC', 'CONSTRAINT_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['TC', 'CONSTRAINT_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_NAME'])
            . ' WHERE '
            . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
            . ' = '
            . $p->quoteTrustedValue($table)
            . ' AND '
            . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
            . ' IN (\'BASE TABLE\', \'VIEW\')';

        $sql .= self::DEFAULT_SCHEMA === $schema
            ? " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} != 'INFORMATION_SCHEMA'"
            : " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} = {$p->quoteTrustedValue($schema)}";

        $sql .= " ORDER BY CASE {$p->quoteIdentifierChain([
     'TC',
     'CONSTRAINT_TYPE',
 ])} WHEN 'PRIMARY KEY' THEN 1 WHEN 'UNIQUE' THEN 2 WHEN 'FOREIGN KEY' THEN 3 ELSE 4 END, {$p->quoteIdentifierChain([
     'TC',
     'CONSTRAINT_NAME',
 ])}, {$p->quoteIdentifierChain(['KCU', 'ORDINAL_POSITION'])}";

        /** @var ResultSetInterface $results */
        $results = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);

        $realName    = null;
        $constraints = [];
        /** @var array{TABLE_NAME: string, CONSTRAINT_NAME: string, CONSTRAINT_TYPE: string, COLUMN_NAME: ?string, MATCH_OPTION: ?string, UPDATE_RULE: ?string, DELETE_RULE: ?string, REFERENCED_TABLE_SCHEMA: ?string, REFERENCED_TABLE_NAME: ?string, REFERENCED_COLUMN_NAME: ?string} $row */
        foreach ($results->toArray() as $row) {
            if ($row['CONSTRAINT_NAME'] !== $realName) {
                $realName           = $row['CONSTRAINT_NAME'];
                $isFK               = 'FOREIGN KEY' === $row['CONSTRAINT_TYPE'];
                $name               = $isFK ? $realName : "_phpdb_{$row['TABLE_NAME']}_{$realName}";
                $constraints[$name] = [
                    'constraint_name' => $name,
                    'constraint_type' => $row['CONSTRAINT_TYPE'],
                    'table_name'      => $row['TABLE_NAME'],
                    'columns'         => [],
                ];
                if ($isFK) {
                    $constraints[$name]['referenced_table_schema'] = $row['REFERENCED_TABLE_SCHEMA'];
                    $constraints[$name]['referenced_table_name']   = $row['REFERENCED_TABLE_NAME'];
                    $constraints[$name]['referenced_columns']      = [];
                    $constraints[$name]['match_option']            = $row['MATCH_OPTION'];
                    $constraints[$name]['update_rule']             = $row['UPDATE_RULE'];
                    $constraints[$name]['delete_rule']             = $row['DELETE_RULE'];
                }
            }
            $constraints[$name]['columns'][] = $row['COLUMN_NAME'];
            if ($isFK) {
                $constraints[$name]['referenced_columns'][] = $row['REFERENCED_COLUMN_NAME'];
            }
        }

        $this->data['constraints'][$schema][$table] = $constraints;

        // phpcs:enable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps
    }

    #[Override]
    protected function loadConstraintDataKeys(string $schema): void
    {
        if (null !== ($this->data['constraint_keys'][$schema] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('constraint_keys', $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['T',   'TABLE_NAME'],
            ['KCU', 'CONSTRAINT_NAME'],
            ['KCU', 'COLUMN_NAME'],
            ['KCU', 'ORDINAL_POSITION'],
        ];

        array_walk($isColumns, static function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql =
            'SELECT '
            . implode(', ', $isColumns)
            . ' FROM '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TABLES'])
            . 'T'
            . ' INNER JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'KEY_COLUMN_USAGE'])
            . 'KCU'
            . ' ON '
            . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['KCU', 'TABLE_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['KCU', 'TABLE_NAME'])
            . ' WHERE '
            . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
            . ' IN (\'BASE TABLE\', \'VIEW\')';

        $sql .= self::DEFAULT_SCHEMA === $schema
            ? " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} != 'INFORMATION_SCHEMA'"
            : " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} = {$p->quoteTrustedValue($schema)}";

        /** @var ResultSetInterface $results */
        $results = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);

        $data = [];
        /** @var array<string, mixed> $row */
        foreach ($results->toArray() as $row) {
            $data[] = array_change_key_case($row, CASE_LOWER);
        }

        $this->data['constraint_keys'][$schema] = $data;
    }

    protected function loadConstraintDataNames(string $schema): void
    {
        if (null !== ($this->data['constraint_names'][$schema] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('constraint_names', $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['TC', 'TABLE_NAME'],
            ['TC', 'CONSTRAINT_NAME'],
            ['TC', 'CONSTRAINT_TYPE'],
        ];

        array_walk($isColumns, static function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql =
            'SELECT '
            . implode(', ', $isColumns)
            . ' FROM '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TABLES'])
            . 'T'
            . ' INNER JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TABLE_CONSTRAINTS'])
            . 'TC'
            . ' ON '
            . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['TC', 'TABLE_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['TC', 'TABLE_NAME'])
            . ' WHERE '
            . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
            . ' IN (\'BASE TABLE\', \'VIEW\')';

        $sql .= self::DEFAULT_SCHEMA === $schema
            ? " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} != 'INFORMATION_SCHEMA'"
            : " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} = {$p->quoteTrustedValue($schema)}";

        /** @var ResultSetInterface $results */
        $results = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);

        $data = [];
        /** @var array<string, mixed> $row */
        foreach ($results->toArray() as $row) {
            $data[] = array_change_key_case($row, CASE_LOWER);
        }

        $this->data['constraint_names'][$schema] = $data;
    }

    #[Override]
    protected function loadConstraintReferences(string $table, string $schema): void
    {
        parent::loadConstraintReferences($table, $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['RC',  'TABLE_NAME'],
            ['RC',  'CONSTRAINT_NAME'],
            ['RC',  'UPDATE_RULE'],
            ['RC',  'DELETE_RULE'],
            ['KCU', 'REFERENCED_TABLE_SCHEMA'],
            ['KCU', 'REFERENCED_TABLE_NAME'],
            ['KCU', 'REFERENCED_COLUMN_NAME'],
        ];

        array_walk($isColumns, static function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql =
            'SELECT '
            . implode(', ', $isColumns)
            . 'FROM '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TABLES'])
            . 'T'
            . ' INNER JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'REFERENTIAL_CONSTRAINTS'])
            . 'RC'
            . ' ON '
            . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['RC', 'TABLE_NAME'])
            . ' INNER JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'KEY_COLUMN_USAGE'])
            . 'KCU'
            . ' ON '
            . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['KCU', 'TABLE_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['RC', 'TABLE_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['KCU', 'TABLE_NAME'])
            . ' AND '
            . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['KCU', 'CONSTRAINT_NAME'])
            . 'WHERE '
            . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
            . ' IN (\'BASE TABLE\', \'VIEW\')';

        $sql .= self::DEFAULT_SCHEMA === $schema
            ? " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} != 'INFORMATION_SCHEMA'"
            : " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} = {$p->quoteTrustedValue($schema)}";

        /** @var ResultSetInterface $results */
        $results = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);

        $data = [];
        /** @var array<string, mixed> $row */
        foreach ($results->toArray() as $row) {
            $data[] = array_change_key_case($row, CASE_LOWER);
        }

        $this->data['constraint_references'][$schema] = $data;
    }

    /**
     * @throws Exception
     */
    #[Override]
    protected function loadSchemaData(): void
    {
        if (null !== ($this->data['schemas'] ?? null)) {
            return;
        }
        $this->prepareDataHierarchy('schemas');

        $p = $this->adapter->getPlatform();

        $sql = <<<SQL
            SELECT {$p->quoteIdentifier('SCHEMA_NAME')}
            FROM {$p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'SCHEMATA'])}
            WHERE {$p->quoteIdentifier('SCHEMA_NAME')} != 'INFORMATION_SCHEMA'
            SQL;

        /** @var ResultSetInterface $results */
        $results = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);

        $schemas = [];
        /** @var array{SCHEMA_NAME: string} $row */
        foreach ($results->toArray() as $row) {
            $schemas[] = $row['SCHEMA_NAME'];
        }

        $this->data['schemas'] = $schemas;
    }

    #[Override]
    protected function loadTableNameData(string $schema): void
    {
        if (null !== ($this->data['table_names'][$schema] ?? null)) {
            return;
        }
        $this->prepareDataHierarchy('table_names', $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['T', 'TABLE_NAME'],
            ['T', 'TABLE_TYPE'],
            ['V', 'VIEW_DEFINITION'],
            ['V', 'CHECK_OPTION'],
            ['V', 'IS_UPDATABLE'],
        ];

        array_walk($isColumns, static function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql =
            'SELECT '
            . implode(', ', $isColumns)
            . ' FROM '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TABLES'])
            . 'T'
            . ' LEFT JOIN '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'VIEWS'])
            . ' V'
            . ' ON '
            . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . '  = '
            . $p->quoteIdentifierChain(['V', 'TABLE_SCHEMA'])
            . ' AND '
            . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
            . '  = '
            . $p->quoteIdentifierChain(['V', 'TABLE_NAME'])
            . ' WHERE '
            . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
            . ' IN (\'BASE TABLE\', \'VIEW\')';

        $sql .= self::DEFAULT_SCHEMA === $schema
            ? " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} != 'INFORMATION_SCHEMA'"
            : " AND {$p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])} = {$p->quoteTrustedValue($schema)}";

        /** @var ResultSetInterface $results */
        $results = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);

        $tables = [];
        /** @var array{TABLE_NAME: string, TABLE_TYPE: string, VIEW_DEFINITION: ?string, CHECK_OPTION: ?string, IS_UPDATABLE: ?string} $row */
        foreach ($results->toArray() as $row) {
            $tables[$row['TABLE_NAME']] = [
                'table_type'      => $row['TABLE_TYPE'],
                'view_definition' => $row['VIEW_DEFINITION'],
                'check_option'    => $row['CHECK_OPTION'],
                'is_updatable'    => 'YES' === $row['IS_UPDATABLE'],
            ];
        }

        $this->data['table_names'][$schema] = $tables;
    }

    #[Override]
    protected function loadTriggerData(string $schema): void
    {
        if (null !== ($this->data['triggers'][$schema] ?? null)) {
            return;
        }

        $this->prepareDataHierarchy('triggers', $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
            // 'TRIGGER_CATALOG',
            // 'TRIGGER_SCHEMA',
            'TRIGGER_NAME',
            'EVENT_MANIPULATION',
            'EVENT_OBJECT_CATALOG',
            'EVENT_OBJECT_SCHEMA',
            'EVENT_OBJECT_TABLE',
            'ACTION_ORDER',
            'ACTION_CONDITION',
            'ACTION_STATEMENT',
            'ACTION_ORIENTATION',
            'ACTION_TIMING',
            'ACTION_REFERENCE_OLD_TABLE',
            'ACTION_REFERENCE_NEW_TABLE',
            'ACTION_REFERENCE_OLD_ROW',
            'ACTION_REFERENCE_NEW_ROW',
            'CREATED',
        ];

        array_walk($isColumns, static function (&$c) use ($p) {
            $c = $p->quoteIdentifier($c);
        });

        $sql =
            'SELECT '
            . implode(', ', $isColumns)
            . ' FROM '
            . $p->quoteIdentifierChain(['INFORMATION_SCHEMA', 'TRIGGERS'])
            . ' WHERE ';

        $sql .= self::DEFAULT_SCHEMA === $schema
            ? "{$p->quoteIdentifier('TRIGGER_SCHEMA')} != 'INFORMATION_SCHEMA'"
            : "{$p->quoteIdentifier('TRIGGER_SCHEMA')} = {$p->quoteTrustedValue($schema)}";

        /** @var ResultSetInterface $results */
        $results = $this->adapter->query($sql, AdapterInterface::QUERY_MODE_EXECUTE);

        $data = [];
        /** @var array{TRIGGER_NAME: string, EVENT_MANIPULATION: string, EVENT_OBJECT_CATALOG: string, EVENT_OBJECT_SCHEMA: string, EVENT_OBJECT_TABLE: string, ACTION_ORDER: string, ACTION_CONDITION: ?string, ACTION_STATEMENT: string, ACTION_ORIENTATION: string, ACTION_TIMING: string, ACTION_REFERENCE_OLD_TABLE: ?string, ACTION_REFERENCE_NEW_TABLE: ?string, ACTION_REFERENCE_OLD_ROW: ?string, ACTION_REFERENCE_NEW_ROW: ?string, CREATED: ?string} $row */
        foreach ($results->toArray() as $row) {
            /** @var array{trigger_name: string, event_manipulation: string, event_object_catalog: string, event_object_schema: string, event_object_table: string, action_order: string, action_condition: ?string, action_statement: string, action_orientation: string, action_timing: string, action_reference_old_table: ?string, action_reference_new_table: ?string, action_reference_old_row: ?string, action_reference_new_row: ?string, created: ?string} $row */
            $row = array_change_key_case($row, CASE_LOWER);
            if (null !== $row['created']) {
                $row['created'] = new DateTime($row['created']);
            }
            $data[$row['trigger_name']] = $row;
        }

        $this->data['triggers'][$schema] = $data;
    }
}
