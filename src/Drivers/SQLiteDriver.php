<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;

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

    protected function updateTable($table, $schema): bool
    {
        $current = $this->getSchema($table);
        if (!$current || $schema == $current) {
            return true;
        }
        //create new table
        $table_tmp = "{$table}_tmp_" . md5(rand());
        $sql = $this->sql_ddl([
            'table' => $table_tmp,
            'schema' => $schema,
        ]);
        if ($this->pdo->exec($sql) === false) {
            return false;
        }
        //copy data into it
        $sql = ["INSERT INTO $table_tmp"];
        $cols = ["json_data"];
        $srcs = ["json_data"];
        foreach ($schema as $path => $col) {
            $cols[] = $col['name'];
            $srcs[] = $this->expandPath($path);
        }
        $sql[] = '(' . implode(',', $cols) . ')';
        $sql[] = 'SELECT';
        $sql[] = implode(',', $srcs);
        $sql[] = "FROM $table";
        $sql = implode(PHP_EOL, $sql);
        if ($this->pdo->exec($sql) === false) {
            return false;
        }
        //remove old table, rename new table to old table
        if ($this->pdo->exec("DROP TABLE $table") === false) {
            return false;
        }
        if ($this->pdo->exec("ALTER TABLE $table_tmp RENAME TO $table") === false) {
            return false;
        }
        //set up indexes
        if (!$this->buildIndexes($table, $schema)) {
            return false;
        }
        //save schema
        $this->saveSchema($table, $schema);
        //return result
        return true;
    }

    protected function addColumns($table, $schema): bool
    {
        //does nothing
        return true;
    }

    protected function removeColumns($table, $schema): bool
    {
        //does nothing
        return true;
    }

    protected function rebuildSchema($table, $schema): bool
    {
        //does nothing
        return true;
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
                return '`' . preg_replace('/^:/', '', $e) . '` = ' . $e;
            },
            array_keys($args['columns'])
        );
        $out = [];
        $out[] = 'UPDATE `' . $args['table'] . '`';
        $out[] = 'SET';
        $out[] = implode(',' . PHP_EOL, $names);
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

    protected function buildIndexes(string $table, array $schema): bool
    {
        $result = true;
        foreach ($schema as $key => $vcol) {
            if (@$vcol['primary']) {
                //sqlite automatically creates this index
            } elseif (@$vcol['unique']) {
                $result = $result &&
                $this->pdo->exec('CREATE UNIQUE INDEX ' . $table . '_' . $vcol['name'] . '_idx on `' . $table . '`(`' . $vcol['name'] . '`)') !== false;
            } elseif (@$vcol['index']) {
                $idxResult = $result &&
                $this->pdo->exec('CREATE INDEX ' . $table . '_' . $vcol['name'] . '_idx on `' . $table . '`(`' . $vcol['name'] . '`)') !== false;
            }
        }
        return $result;
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

    protected function sql_create_schema_table(): string
    {
        return <<<EOT
CREATE TABLE IF NOT EXISTS `destructr_schema`(
    schema_time BIGINT NOT NULL,
    schema_table VARCHAR(100) NOT NULL,
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
