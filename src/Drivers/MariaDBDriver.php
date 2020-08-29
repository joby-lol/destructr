<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

/**
 * What this driver supports: MariaDB >= 10.2.7
 */
class MariaDBDriver extends MySQLDriver
{
    protected function sql_ddl(array $args = []): string
    {
        $out = [];
        $out[] = "CREATE TABLE IF NOT EXISTS `{$args['table']}` (";
        $lines = [];
        $lines[] = "`json_data` JSON DEFAULT NULL";
        foreach ($args['schema'] as $path => $col) {
            $line = "`{$col['name']}` {$col['type']} GENERATED ALWAYS AS (" . $this->expandPath($path) . ") VIRTUAL";
            $lines[] = $line;
        }
        $out[] = implode(',' . PHP_EOL, $lines);
        $out[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $out = implode(PHP_EOL, $out);
        return $out;
    }

    protected function buildIndexes(string $table, array $schema):bool
    {
        $out = true;
        foreach ($schema as $path => $col) {
            if (@$col['primary']) {
                $out = $out && $this->pdo->exec(
                    "CREATE UNIQUE INDEX `{$table}_{$col['name']}_idx` ON {$table} (`{$col['name']}`) USING BTREE"
                ) !== false;
            } elseif (@$col['unique'] && $as = @$col['index']) {
                $out = $out && $this->pdo->exec(
                    "CREATE UNIQUE INDEX `{$table}_{$col['name']}_idx` ON {$table} (`{$col['name']}`) USING $as"
                ) !== false;
            } elseif ($as = @$col['index']) {
                $out = $out && $this->pdo->exec(
                    "CREATE INDEX `{$table}_{$col['name']}_idx` ON {$table} (`{$col['name']}`) USING $as"
                ) !== false;
            }
        }
        return $out;
    }

    protected function addColumns($table, $schema): bool
    {
        $out = true;
        foreach ($schema as $path => $col) {
            $line = "ALTER TABLE `{$table}` ADD COLUMN `${col['name']}` {$col['type']} GENERATED ALWAYS AS (" . $this->expandPath($path) . ")";
            if (@$col['primary']) {
                $line .= ' PERSISTENT;';
            } else {
                $line .= ' VIRTUAL;';
            }
            $out = $out &&
            $this->pdo->exec($line) !== false;
        }
        return $out;
    }
}
