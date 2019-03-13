<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr\LegacyDrivers;

use Destructr\DSOInterface;
use Destructr\Factory;

/**
 * What this driver supports: Version of SQLite3 in PHP environments that allow
 * pdo::sqliteCreateFunction
 *
 * Overally this driver is quite safe and reliable. Definitely the most safe of
 * all the legacy drivers. The performance isn't even that bad. It benchmarks
 * close to the same speed as MySQL 5.7, even. The benchmarks are only operating
 * on databases of 500 objects though, so ymmv.
 */
class SQLiteDriver extends AbstractLegacyDriver
{
    public function &pdo(\PDO &$pdo=null) : ?\PDO
    {
        if ($pdo) {
            $this->pdo = $pdo;
            /*
            What we're doing here is adding a custom function to SQLite so that it
            can extract JSON values. It's not fast, but it does let us use JSON
            fairly seamlessly.
             */
            $this->pdo->sqliteCreateFunction(
                'DESTRUCTR_JSON_EXTRACT',
                '\\Destructr\\LegacyDrivers\\SQLiteDriver::JSON_EXTRACT',
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

    public function createTable(string $table, array $virtualColumns) : bool
    {
        $sql = $this->sql_ddl([
            'table'=>$table,
        ]);
        $out = $this->pdo->exec($sql) !== false;
        foreach (Factory::CORE_VIRTUAL_COLUMNS as $key => $vcol) {
            $idxResult = true;
            if (@$vcol['unique']) {
                $idxResult = $this->pdo->exec('CREATE UNIQUE INDEX '.$table.'_'.$vcol['name'].'_idx on `'.$table.'`(`'.$vcol['name'].'`)') !== false;
            } elseif (@$vcol['index']) {
                $idxResult = $this->pdo->exec('CREATE INDEX '.$table.'_'.$vcol['name'].'_idx on `'.$table.'`(`'.$vcol['name'].'`)') !== false;
            }
            if (!$idxResult) {
                $out = false;
            }
        }
        return $out;
    }

    protected function sql_ddl($args=array())
    {
        $out = [];
        $out[] = "CREATE TABLE `{$args['table']}` (";
        $lines = [];
        $lines[] = "`json_data` TEXT DEFAULT NULL";
        foreach (Factory::CORE_VIRTUAL_COLUMNS as $path => $col) {
            $line = "`{$col['name']}` {$col['type']}";
            if (@$col['primary']) {
                $line .= ' PRIMARY KEY';
            }
            $lines[] = $line;
        }
        $out[] = implode(','.PHP_EOL, $lines);
        $out[] = ");";
        return implode(PHP_EOL, $out);
    }

    protected function expandPath(string $path) : string
    {
        return "DESTRUCTR_JSON_EXTRACT(`json_data`,'$.{$path}')";
    }

    public function json_encode($a, ?array &$b = null, string $prefix = '')
    {
        return json_encode($a);
    }
}
