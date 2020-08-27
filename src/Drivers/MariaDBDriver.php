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
        foreach ($args['virtualColumns'] as $path => $col) {
            $line = "`{$col['name']}` {$col['type']} GENERATED ALWAYS AS (" . $this->expandPath($path) . ")";
            if (@$col['primary']) {
                $line .= ' PERSISTENT';
            } else {
                $line .= ' VIRTUAL';
            }
            $lines[] = $line;
        }
        foreach ($args['virtualColumns'] as $path => $col) {
            if (@$col['primary']) {
                $lines[] = "UNIQUE KEY (`{$col['name']}`)";
            } elseif (@$col['unique'] && $as = @$col['index']) {
                $lines[] = "UNIQUE KEY `{$args['table']}_{$col['name']}_idx` (`{$col['name']}`) USING $as";
            } elseif ($as = @$col['index']) {
                $lines[] = "KEY `{$args['table']}_{$col['name']}_idx` (`{$col['name']}`) USING $as";
            }
        }
        $out[] = implode(',' . PHP_EOL, $lines);
        $out[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        return implode(PHP_EOL, $out);
    }
}
