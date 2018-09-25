<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr\Drivers;

/**
 * What this driver supports: MySQL and MariaDB databases new enough to support
 * JSON functions. This means:
 *  * MySQL >= 5.7
 *  * MariaDB >= 10.2
 */
class MySQLDriver extends AbstractDriver
{
    /**
     * Within the search we expand strings like ${dso.id} into JSON queries.
     * Note that the Search will have already had these strings expanded into
     * column names if there are virtual columns configured for them. That
     * happens in the Factory before it gets here.
     */
    protected function sql_select($args)
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
            $out[] = "WHERE ".$where;
        }
        //order statement
        if ($order !== null) {
            $out[] = "ORDER BY ".$order;
        }
        //limit
        if ($limit !== null) {
            $out[] = "LIMIT ".$limit;
        }
        //offset
        if ($offset !== null) {
            $out[] = "OFFSET ".$offset;
        }
        //return
        return implode(PHP_EOL, $out).';';
    }

    protected function sql_ddl($args=array())
    {
        $out = [];
        $out[] = "CREATE TABLE `{$args['table']}` (";
        $lines = [];
        $lines[] = "`json_data` JSON DEFAULT NULL";
        foreach ($args['virtualColumns'] as $path => $col) {
            $line = "`{$col['name']}` {$col['type']} GENERATED ALWAYS AS (".$this->expandPath($path).")";
            if (@$col['primary']) {
                $line .= ' PERSISTENT';
            } else {
                $line .= ' VIRTUAL';
            }
            $lines[] = $line;
        }
        foreach ($args['virtualColumns'] as $path => $col) {
            if (@$col['primary']) {
                $lines[] = "PRIMARY KEY (`{$col['name']}`)";
            } elseif (@$col['unique'] && $as = @$col['index']) {
                $lines[] = "UNIQUE KEY `{$args['table']}_{$col['name']}_idx` (`{$col['name']}`) USING $as";
            } elseif ($as = @$col['index']) {
                $lines[] = "KEY `{$args['table']}_{$col['name']}_idx` (`{$col['name']}`) USING $as";
            }
        }
        $out[] = implode(','.PHP_EOL, $lines);
        $out[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        return implode(PHP_EOL, $out);
    }

    protected function expandPath(string $path) : string
    {
        return "JSON_UNQUOTE(JSON_EXTRACT(`json_data`,'$.{$path}'))";
    }

    protected function sql_setJSON($args)
    {
        return 'UPDATE `'.$args['table'].'` SET `json_data` = :data WHERE `dso_id` = :dso_id;';
    }

    protected function sql_insert($args)
    {
        return "INSERT INTO `{$args['table']}` (`json_data`) VALUES (:data);";
    }

    protected function sql_delete($args)
    {
        return 'DELETE FROM `'.$args['table'].'` WHERE `dso_id` = :dso_id;';
    }
}
