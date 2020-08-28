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
            $line = "`{$col['name']}` {$col['type']} GENERATED ALWAYS AS (" . $this->expandPath($path) . ")";
            if (@$col['primary']) {
                $line .= ' PERSISTENT';
            } else {
                $line .= ' VIRTUAL';
            }
            $lines[] = $line;
        }
        $out[] = implode(',' . PHP_EOL, $lines);
        $out[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $out = implode(PHP_EOL, $out);
        return $out;
    }
}
