<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;
use Destructr\Search;
use Flatrr\FlatArray;

/**
 * What this driver supports: Any version of SQLite3 in PHP environments that allow
 * pdo::sqliteCreateFunction
 *
 * Note that unlike databases with native JSON functions, this driver's generated
 * columns are NOT generated in the database. They are updated by this class whenever
 * the data they reference changes. This doesn't matter much if you're doing all
 * your updating through Destructr, but is something to be cognizent of if your
 * data is being updated outside Destructr.
 */
class SQLiteDriver extends AbstractSQLDriver
{
    public function update(string $table, DSOInterface $dso): bool
    {
        if (!$dso->changes() && !$dso->removals()) {
            return true;
        }
        $columns = $this->dso_columns($dso);
        $s = $this->getStatement(
            'set_json',
            [
                'table' => $table,
                'columns' => $columns,
            ]
        );
        return $s->execute($columns);
    }

    public function insert(string $table, DSOInterface $dso): bool
    {
        $columns = $this->dso_columns($dso);
        $s = $this->getStatement(
            'insert',
            [
                'table' => $table,
                'columns' => $columns,
            ]
        );
        return $s->execute($columns);
    }

    protected function sql_insert(array $args): string
    {
        $out = [];
        $names = array_map(
            function ($e) {
                return preg_replace('/^:/', '', $e);
            },
            array_keys($args['columns'])
        );
        $out[] = 'INSERT INTO `' . $args['table'] . '`';
        $out[] = '(`' . implode('`,`', $names) . '`)';
        $out[] = 'VALUES (:' . implode(',:', $names) . ')';
        $out = implode(PHP_EOL, $out) . ';';
        return $out;
    }

    protected function sql_set_json(array $args): string
    {
        $names = array_map(
            function ($e) {
                return '`'.preg_replace('/^:/', '', $e).'` = '.$e;
            },
            array_keys($args['columns'])
        );
        $out = [];
        $out[] = 'UPDATE `' . $args['table'] . '`';
        $out[] = 'SET';
        $out[] = implode(','.PHP_EOL,$names);
        $out[] = 'WHERE `dso_id` = :dso_id';
        $out = implode(PHP_EOL, $out) . ';';
        return $out;
    }

    public function delete(string $table, DSOInterface $dso): bool
    {
        $s = $this->getStatement(
            'delete',
            ['table' => $table]
        );
        return $s->execute([
            ':dso_id' => $dso['dso.id'],
        ]);
    }

    /**
     * Used to extract a list of column/parameter names for a given DSO, based
     * on the current values.
     *
     * @param DSOInterface $dso
     * @return void
     */
    protected function dso_columns(DSOInterface $dso)
    {
        $columns = [':json_data' => json_encode($dso->get())];
        foreach ($this->getSchema($dso->factory()->table()) ?? [] as $vk => $vv) {
            $columns[':' . $vv['name']] = $dso->get($vk);
        }
        return $columns;
    }

    /**
     * Intercept calls to set PDO, and add a custom function to SQLite so that it
     * can extract JSON values. It's not actually terribly slow, and allows us to
     * use JSON seamlessly, almost as if it were native.
     *
     * @param \PDO $pdo
     * @return \PDO|null
     */
    public function pdo(\PDO $pdo = null): ?\PDO
    {
        if ($pdo) {
            $this->pdo = $pdo;
            $this->pdo->sqliteCreateFunction(
                'DESTRUCTR_JSON_EXTRACT',
                '\\Destructr\\Drivers\\SQLiteDriver::JSON_EXTRACT',
                2
            );
        }
        return $this->pdo;
    }

    public static function JSON_EXTRACT($json, $path)
    {
        $path = substr($path, 2);
        $path = explode('.', $path);
        $arr = json_decode($json, true);
        $out = &$arr;
        while ($key = array_shift($path)) {
            if (isset($out[$key])) {
                $out = &$out[$key];
            } else {
                return null;
            }
        }
        return @"$out";
    }

    protected function buildIndexes(string $table, array $schema)
    {
        foreach ($schema as $key => $vcol) {
            $idxResult = true;
            if (@$vcol['primary']) {
                //sqlite automatically creates this index
            } elseif (@$vcol['unique']) {
                $idxResult = $this->pdo->exec('CREATE UNIQUE INDEX ' . $table . '_' . $vcol['name'] . '_idx on `' . $table . '`(`' . $vcol['name'] . '`)') !== false;
            } elseif (@$vcol['index']) {
                $idxResult = $this->pdo->exec('CREATE INDEX ' . $table . '_' . $vcol['name'] . '_idx on `' . $table . '`(`' . $vcol['name'] . '`)') !== false;
            }
            if (!$idxResult) {
                $out = false;
            }
        }
    }

    protected function rebuildSchema($table, $schema)
    {
        //TODO: fix all columns to match JSON, this will be SLOW
    }

    protected function sql_ddl(array $args = []): string
    {
        $out = [];
        $out[] = "CREATE TABLE IF NOT EXISTS `{$args['table']}` (";
        $lines = [];
        $lines[] = "`json_data` TEXT DEFAULT NULL";
        foreach ($args['schema'] as $path => $col) {
            $line = "`{$col['name']}` {$col['type']}";
            if (@$col['primary']) {
                $line .= ' PRIMARY KEY';
            }
            $lines[] = $line;
        }
        $out[] = implode(',' . PHP_EOL, $lines);
        $out[] = ");";
        $out = implode(PHP_EOL, $out);
        return $out;
    }

    protected function expandPath(string $path): string
    {
        return "DESTRUCTR_JSON_EXTRACT(`json_data`,'$.{$path}')";
    }

    protected function addColumns($table,$schema):bool
    {
        //TODO: finish this
        return false;
    }

    protected function removeColumns($table,$schema):bool
    {
        //TODO: finish this
        return false;
    }

    protected function sql_create_schema_table(): string
    {
        return <<<EOT
CREATE TABLE IF NOT EXISTS `destructr_schema`(
    schema_table VARCHAR(100) PRIMARY KEY NOT NULL,
    schema_schema TEXT NOT NULL
);
EOT;
    }

    protected function sql_table_exists(string $table): string
    {
        $table = preg_replace('/[^a-zA-Z0-9\-_]/', '', $table);
        return 'SELECT 1 FROM ' . $table . ' LIMIT 1';
    }
}
