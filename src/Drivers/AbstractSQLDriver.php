<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\Drivers;

use Destructr\DSOInterface;
use Destructr\Search;
use PDO;

abstract class AbstractSQLDriver extends AbstractDriver
{
    public $lastPreparationErrorOn;
    public $pdo;
    protected $schemas = [];

    abstract protected function sql_ddl(array $args = []): string;
    abstract protected function expandPath(string $path): string;
    abstract protected function sql_set_json(array $args): string;
    abstract protected function sql_insert(array $args): string;
    abstract protected function sql_create_schema_table(): string;
    abstract protected function addColumns($table, $schema): bool;
    abstract protected function removeColumns($table, $schema): bool;
    abstract protected function sql_table_exists(string $table): string;
    abstract protected function buildIndexes(string $table, array $schema): bool;
    abstract protected function rebuildSchema($table, $schema): bool;

    public function __construct(string $dsn = null, string $username = null, string $password = null, array $options = null)
    {
        if ($dsn) {
            if (!($pdo = new \PDO($dsn, $username, $password, $options))) {
                throw new \Exception("Error creating PDO connection");
            }
            $this->pdo($pdo);
        }
    }

    public function tableExists(string $table): bool
    {
        $stmt = $this->pdo()->prepare($this->sql_table_exists($table));
        if ($stmt && $stmt->execute() !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function createSchemaTable()
    {
        $this->pdo->exec($this->sql_create_schema_table());
        return $this->tableExists('destructr_schema');
    }

    public function pdo(\PDO $pdo = null): ?\PDO
    {
        if ($pdo) {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->pdo = $pdo;
        }
        return $this->pdo;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    protected function expandPaths($value)
    {
        if ($value === null) {
            return null;
        }
        $value = preg_replace_callback(
            '/\$\{([^\}\\\]+)\}/',
            function ($matches) {
                return $this->expandPath($matches[1]);
            },
            $value
        );
        return $value;
    }

    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }

    public function prepareEnvironment(string $table, array $schema): bool
    {
        $this->beginTransaction();
        if ($this->createSchemaTable() && $this->createTable($table, $schema)) {
            $this->commit();
            return true;
        } else {
            $this->rollBack();
            return false;
        }
    }

    public function updateEnvironment(string $table, array $schema): bool
    {
        $this->beginTransaction();
        if ($this->updateTable($table, $schema)) {
            $this->commit();
            return true;
        } else {
            $this->rollBack();
            return false;
        }
    }

    protected function updateTable($table, $schema): bool
    {
        $current = $this->getSchema($table);
        $new = $schema;
        if (!$current || $schema == $current) {
            return true;
        }
        //do nothing with totally unchanged columns
        foreach ($current as $c_id => $c) {
            foreach ($schema as $n_id => $n) {
                if ($n == $c && $n_id == $c_id) {
                    unset($current[$c_id]);
                    unset($new[$n_id]);
                }
            }
        }
        $removed = $current;
        $added = $new;
        //apply changes
        $out = [
            'removeColumns' => $this->removeColumns($table, $removed),
            'addColumns' => $this->addColumns($table, $added),
            'rebuildSchema' => $this->rebuildSchema($table, $schema),
            'buildIndexes' => $this->buildIndexes($table, $schema),
            'saveSchema' => $this->saveSchema($table, $schema),
        ];
        foreach ($out as $k => $v) {
            if (!$v) {
                user_error("An error occurred during updateTable for $table. The error happened during $v.", E_USER_WARNING);
            }
        }
        return !!array_filter($out);
    }

    public function createTable(string $table, array $schema): bool
    {
        // check if table exists, if it doesn't, save into schema table
        if (!$this->tableExists($table)) {
            $this->saveSchema($table, $schema);
        } else {
            return true;
        }
        // create table from scratch
        $sql = $this->sql_ddl([
            'table' => $table,
            'schema' => $schema,
        ]);
        $out = $this->pdo->exec($sql) !== false;
        if ($out) {
            $this->buildIndexes($table, $schema);
        }else {
            var_dump($this->errorInfo());
        }
        return $out;
    }

    public function getSchema(string $table): ?array
    {
        if (!isset($this->schemas[$table])) {
            $s = $this->getStatement(
                'get_schema',
                ['table' => $table]
            );
            if (!$s->execute(['table' => $table])) {
                $this->schemas[$table] = null;
            } else {
                if ($row = $s->fetch(\PDO::FETCH_ASSOC)) {
                    $this->schemas[$table] = @json_decode($row['schema_schema'], true);
                } else {
                    $this->schemas[$table] = null;
                }
            }
        }
        return @$this->schemas[$table];
    }

    public function saveSchema(string $table, array $schema): bool
    {
        $out = $this->pdo->exec(
            $this->sql_save_schema($table, $schema)
        ) !== false;
        return $out;
    }

    protected function sql_save_schema(string $table, array $schema)
    {
        $time = time();
        $table = $this->pdo->quote($table);
        $schema = $this->pdo->quote(json_encode($schema));
        return <<<EOT
INSERT INTO `destructr_schema`
(schema_time,schema_table,schema_schema)
VALUES ($time,$table,$schema);
EOT;
    }

    protected function sql_get_schema(array $args)
    {
        return <<<EOT
SELECT * FROM `destructr_schema`
WHERE `schema_table` = :table
ORDER BY `schema_time` desc
LIMIT 1
EOT;
    }

    public function update(string $table, DSOInterface $dso): bool
    {
        if (!$dso->changes() && !$dso->removals()) {
            return true;
        }
        $s = $this->getStatement(
            'set_json',
            ['table' => $table]
        );
        return $s->execute([
            ':dso_id' => $dso['dso.id'],
            ':data' => json_encode($dso->get()),
        ]);
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

    public function count(string $table, Search $search, array $params): int
    {
        $s = $this->getStatement(
            'count',
            ['table' => $table, 'search' => $search]
        );
        if (!$s->execute($params)) {
            return null;
        }
        return intval($s->fetchAll(\PDO::FETCH_COLUMN)[0]);
    }

    public function select(string $table, Search $search, array $params)
    {
        $s = $this->getStatement(
            'select',
            ['table' => $table, 'search' => $search]
        );
        if (!$s->execute($params)) {
            return [];
        }
        return @$s->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert(string $table, DSOInterface $dso): bool
    {
        return $this->getStatement(
            'insert',
            ['table' => $table]
        )->execute(
            [':data' => json_encode($dso->get())]
        );
    }

    protected function getStatement(string $type, $args = array()): \PDOStatement
    {
        $fn = 'sql_' . $type;
        if (!method_exists($this, $fn)) {
            throw new \Exception("Error getting SQL statement, driver doesn't have a method named $fn");
        }
        $sql = $this->$fn($args);
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt) {
            $this->lastPreparationErrorOn = $sql;
            throw new \Exception("Error preparing statement: " . implode(': ', $this->pdo->errorInfo()), 1);
        }
        return $stmt;
    }

    /**
     * Within the search we expand strings like ${dso.id} into JSON queries.
     * Note that the Search will have already had these strings expanded into
     * column names if there are virtual columns configured for them. That
     * happens in the Factory before it gets here.
     */
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

    protected function sql_delete(array $args): string
    {
        return 'DELETE FROM `' . $args['table'] . '` WHERE `dso_id` = :dso_id;';
    }

}
