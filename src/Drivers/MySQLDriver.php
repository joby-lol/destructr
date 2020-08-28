<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

/**
 * What this driver supports: MySQL >= 5.7.8
 */
class MySQLDriver extends AbstractSQLDriver
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
                $line .= ' STORED';
            } else {
                $line .= ' VIRTUAL';
            }
            $lines[] = $line;
        }
        foreach ($args['schema'] as $path => $col) {
            if (@$col['primary']) {
                $lines[] = "PRIMARY KEY (`{$col['name']}`)";
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

    protected function expandPath(string $path): string
    {
        return "JSON_UNQUOTE(JSON_EXTRACT(`json_data`,'$.{$path}'))";
    }

    protected function sql_set_json(array $args): string
    {
        return 'UPDATE `' . $args['table'] . '` SET `json_data` = :data WHERE `dso_id` = :dso_id;';
    }

    protected function sql_insert(array $args): string
    {
        return "INSERT INTO `{$args['table']}` (`json_data`) VALUES (:data);";
    }

    protected function updateColumns($table,$schema):bool
    {
        //TODO: finish this
        var_dump($table,$schema);
        return false;
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
CREATE TABLE `destructr_schema` (
    `schema_table` varchar(100) NOT NULL,
    `schema_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schema_schema`)),
    PRIMARY KEY (`schema_table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT;
    }
}
