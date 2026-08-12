<?php

declare(strict_types=1);

namespace PhpDbIntegrationTest\Mysql\Pdo;

use Exception;
use PhpDb\Adapter\Driver\Pdo\Result as PdoResult;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Exception\RuntimeException;
use PhpDb\ResultSet\ResultSet;
use PhpDb\Sql\Sql;
use PhpDbIntegrationTest\Mysql\Container\TestAsset\SetupTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class QueryTest extends TestCase
{
    use SetupTrait;

    /**
     * @psalm-return array<array-key, array{
     *     0: string,
     *     1: array|array<string, mixed>,
     *     2: array<string, mixed>
     * }>
     */
    public static function getQueriesWithRowResult(): array
    {
        return [
            ['SELECT * FROM test WHERE id = ?', [1], ['id' => 1, 'name' => 'foo', 'value' => 'bar']],
            ['SELECT * FROM test WHERE id = :id', [':id' => 1], ['id' => 1, 'name' => 'foo', 'value' => 'bar']],
            ['SELECT * FROM test WHERE id = :id', ['id' => 1], ['id' => 1, 'name' => 'foo', 'value' => 'bar']],
            ['SELECT * FROM test WHERE name = ?', ['123'], ['id' => '4', 'name' => '123', 'value' => 'bar']],
            [
                // name is string, but given parameter is int, can lead to unexpected result
                'SELECT * FROM test WHERE name = ?',
                [123],
                ['id' => '3', 'name' => '123a', 'value' => 'bar'],
            ],
        ];
    }

    /**
     * @see https://github.com/laminas/laminas-db/issues/47
     */
    #[Test]
    public function namedParameters(): void
    {
        static::assertNotNull($this->adapter);
        $sql = new Sql($this->adapter);

        $insert = $sql->update('test');
        $insert->set([
            'name'  => ':name',
            'value' => ':value',
        ])->where(['id' => ':id']);
        /** @var StatementInterface $stmt */
        $stmt = $sql->prepareStatementForSqlObject($insert);
        static::assertInstanceOf(StatementInterface::class, $stmt);

        //positional parameters
        $stmt->execute([
            'foo',
            'bar',
            1,
        ]);

        //"mapped" named parameters
        $stmt->execute([
            'c_0'    => 'foo',
            'c_1'    => 'bar',
            'where1' => 1,
        ]);

        //real named parameters
        $stmt->execute([
            'id'    => 1,
            'name'  => 'foo',
            'value' => 'bar',
        ]);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('getQueriesWithRowResult')]
    public function query(string $query, array $params, array $expected): void
    {
        /** @todo Have AdapterInterface implement query */
        $result = $this->getAdapter()->query($query, $params);
        static::assertInstanceOf(ResultSet::class, $result);
        $current = $result->current();
        // test as array value
        static::assertEquals($expected, (array) $current);
        // test as object value
        /** @var string $value */
        foreach ($expected as $key => $value) {
            static::assertEquals($value, $current->$key);
        }
    }

    #[Test]
    public function selectResultCountReturnsActualRowCount(): void
    {
        $result = $this->getAdapter()->query('SELECT * FROM test WHERE value = ?', ['bar']);
        static::assertInstanceOf(ResultSet::class, $result);
        static::assertSame(3, $result->count());
    }

    #[Test]
    public function selectResultCountReturnsZeroForNoResults(): void
    {
        $result = $this->getAdapter()->query('SELECT * FROM test WHERE name = ?', ['nonexistent']);
        static::assertInstanceOf(ResultSet::class, $result);
        static::assertSame(0, $result->count());
    }

    #[Test]
    public function selectResultCountWithWhereClause(): void
    {
        $result = $this->getAdapter()->query('SELECT * FROM test WHERE name = ?', ['foo']);
        static::assertInstanceOf(ResultSet::class, $result);
        static::assertSame(1, $result->count());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function selectWithNotPermittedBindParamName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->getAdapter()->query('SET @@session.time_zone = :tz$', [':tz$' => 'SYSTEM']);
    }

    /**
     * @see https://github.com/zendframework/zend-db/issues/288
     *
     * @throws Exception
     */
    #[Test]
    public function setSessionTimeZone(): void
    {
        $result = $this->getAdapter()->query('SET @@session.time_zone = :tz', [':tz' => 'SYSTEM']);
        static::assertInstanceOf(PdoResult::class, $result);
    }
}
