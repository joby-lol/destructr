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
        $out[] = implode(',' . PHP_EOL, $lines);
        $out[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $out = implode(PHP_EOL, $out);
        return $out;
    }

    protected function buildIndexes(string $table, array $schema): bool
    {
        foreach ($schema as $path => $col) {
            try {
                if (@$col['primary']) {
                    $this->pdo->exec(
                        "CREATE UNIQUE INDEX `{$table}_{$col['name']}_idx` ON {$table} (`{$col['name']}`) USING BTREE"
                    );
                } elseif (@$col['unique'] && $as = @$col['index']) {
                    $this->pdo->exec(
                        "CREATE UNIQUE INDEX `{$table}_{$col['name']}_idx` ON {$table} (`{$col['name']}`) USING $as"
                    );
                } elseif ($as = @$col['index']) {
                    $this->pdo->exec(
                        "CREATE INDEX `{$table}_{$col['name']}_idx` ON {$table} (`{$col['name']}`) USING $as"
                    );
                }
            } catch (\Throwable $th) {
            }
        }
        return true;
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

    protected function addColumns($table, $schema): bool
    {
        $out = true;
        foreach ($schema as $path => $col) {
            $line = "ALTER TABLE `{$table}` ADD COLUMN `${col['name']}` {$col['type']} GENERATED ALWAYS AS (" . $this->expandPath($path) . ")";
            if (@$col['primary']) {
                $line .= ' STORED;';
            } else {
                $line .= ' VIRTUAL;';
            }
            $out = $out &&
                $this->pdo->exec($line) !== false;
        }
        return $out;
    }

    protected function removeColumns($table, $schema): bool
    {
        $out = true;
        foreach ($schema as $path => $col) {
            $out = $out &&
                $this->pdo->exec("ALTER TABLE `{$table}` DROP COLUMN `${col['name']}`;") !== false;
        }
        return $out;
    }

    protected function rebuildSchema($table, $schema): bool
    {
        //this does nothing in databases that can generate columns themselves
        return true;
    }

    protected function sql_create_schema_table(): string
    {
        return <<<EOT
CREATE TABLE `destructr_schema` (
    `schema_time` bigint NOT NULL,
    `schema_table` varchar(100) NOT NULL,
    `schema_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schema_schema`)),
    PRIMARY KEY (`schema_time`,`schema_table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT;
    }

    protected function sql_table_exists(string $table): string
    {
        $table = preg_replace('/[^a-zA-Z0-9\-_]/', '', $table);
        return 'SELECT 1 FROM ' . $table . ' LIMIT 1';
    }
}
