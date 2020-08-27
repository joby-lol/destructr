<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;
use Destructr\Factory;
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
class SQLiteDriver extends AbstractDriver
{
    public function select(string $table, Search $search, array $params)
    {
        $results = parent::select($table, $search, $params);
        foreach ($results as $rkey => $row) {
            $new = new FlatArray();
            foreach (json_decode($row['json_data'], true) as $key => $value) {
                $new->set(str_replace('|', '.', $key), $value);
            }
            $results[$rkey]['json_data'] = json_encode($new->get());
        }
        return $results;
    }

    protected function sql_count(array $args): string
    {
        //extract query parts from Search and expand paths
        $where = $this->expandPaths($args['search']->where());
        //select from
        $out = ["SELECT count(dso_id) FROM `{$args['table']}`"];
        //where statement
        if ($where !== null) {
            $out[] = "WHERE " . $where;
        }
        //return
        return implode(PHP_EOL, $out) . ';';
    }

    protected function sql_select(array $args): string
    {
        //extract query parts from Search and expand paths
        $where = $this->expandPaths($args['search']->where());
        $order = $this->expandPaths($args['search']->order());
        $limit = $args['search']->limit();
        $offset = $args['search']->offset();
        //select from
        $out = ["SELECT * FROM `{$args['table']}`"];
        //where statement
        if ($where !== null) {
            $out[] = "WHERE " . $where;
        }
        //order statement
        if ($order !== null) {
            $out[] = "ORDER BY " . $order;
        }
        //limit
        if ($limit !== null) {
            $out[] = "LIMIT " . $limit;
        }
        //offset
        if ($offset !== null) {
            $out[] = "OFFSET " . $offset;
        }
        //return
        return implode(PHP_EOL, $out) . ';';
    }

    public function update(string $table, DSOInterface $dso): bool
    {
        if (!$dso->changes() && !$dso->removals()) {
            return true;
        }
        $columns = $this->dso_columns($dso);
        $s = $this->getStatement(
            'setJSON',
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

    protected function sql_setJSON(array $args): string
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

    protected function sql_delete(array $args): string
    {
        return 'DELETE FROM `' . $args['table'] . '` WHERE `dso_id` = :dso_id;';
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
        $columns = [':json_data' => $this->json_encode($dso->get())];
        foreach ($dso->factory()->virtualColumns() as $vk => $vv) {
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

    public function createTable(string $table, array $virtualColumns): bool
    {
        $sql = $this->sql_ddl([
            'table' => $table,
            'virtualColumns' => $virtualColumns,
        ]);
        $out = $this->pdo->exec($sql) !== false;
        foreach ($virtualColumns as $key => $vcol) {
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
        return $out;
    }

    protected function sql_ddl(array $args = []): string
    {
        $out = [];
        $out[] = "CREATE TABLE IF NOT EXISTS `{$args['table']}` (";
        $lines = [];
        $lines[] = "`json_data` TEXT DEFAULT NULL";
        foreach ($args['virtualColumns'] as $path => $col) {
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

    public function json_encode($a, ?array &$b = null, string $prefix = '')
    {
        return json_encode($a);
    }
}
