<?php
/* Digraph CMS: Destructr | https://github.com/digraphcms/destructr | MIT License */
namespace Digraph\Destructr;

use mofodojodino\ProfanityFilter\Check;

/**
 * The Factory is responsible for keeping track of which columns may or may not
 * be configured as virtual columns (although in the future for NoSQL databases
 * this may not be relevant).
 *
 * The overall responsibilities of the Factory are:
 *  * Tracking which table is to be used
 *  * Holding the driver to be used
 *  * Creating DSOs and passing itself to them
 *  * Calling its own and the DSO's hook_create() and hook_update() methods
 *  * Passing DSOs that need CRUD-ing to the appropriate Driver methods
 *  * Creating Search objects
 *  * Executing Searches (which largely consists of passing them to the Driver)
 *  * Inspecting unstructured data straight from the database and figuring out what class to make it (defaults to just DSO)
 */
class Factory implements DSOFactoryInterface
{
    const ID_CHARS = 'abcdefghijkmnorstuvwxyz0123456789';
    const ID_LENGTH = 16;

    protected $driver;
    protected $table;
    /**
     * Virtual columns are only supported by modern SQL servers. Most of the
     * legacy drivers will only use the ones defined in CORE_VIRTUAL_COLUMNS,
     * but that should be handled automatically.
     */
    protected $virtualColumns = [
        'dso.id' => [
            'name'=>'dso_id',
            'type'=>'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true
        ],
        'dso.type' => [
            'name'=>'dso_type',
            'type'=>'VARCHAR(30)',
            'index'=>'BTREE'
        ],
        'dso.deleted' => [
            'name'=>'dso_deleted',
            'type'=>'BIGINT',
            'index'=>'BTREE'
        ]
    ];
    /**
     * This cannot be modified by extending classes, it's used by legacy drivers
     */
    const CORE_VIRTUAL_COLUMNS = [
        'dso.id' => [
            'name'=>'dso_id',
            'type'=>'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true
        ],
        'dso.type' => [
            'name'=>'dso_type',
            'type'=>'VARCHAR(30)',
            'index'=>'BTREE'
        ],
        'dso.deleted' => [
            'name'=>'dso_deleted',
            'type'=>'BIGINT',
            'index'=>'BTREE'
        ]
    ];

    public function __construct(Drivers\DSODriverInterface &$driver, string $table)
    {
        $this->driver = $driver;
        $this->table = $table;
    }

    protected function hook_create(DSOInterface &$dso)
    {
        if (!$dso->get('dso.id')) {
            $dso->set('dso.id', static::generate_id(static::ID_CHARS, static::ID_LENGTH), true);
        }
        if (!$dso->get('dso.created.date')) {
            $dso->set('dso.created.date', time());
        }
        if (!$dso->get('dso.created.user')) {
            $dso->set('dso.created.user', ['ip'=>@$_SERVER['REMOTE_ADDR']]);
        }
    }

    protected function hook_update(DSOInterface &$dso)
    {
        $dso->set('dso.modified.date', time());
        $dso->set('dso.modified.user', ['ip'=>@$_SERVER['REMOTE_ADDR']]);
    }

    public function class(array $data) : ?string
    {
        return null;
    }

    public function delete(DSOInterface &$dso, bool $permanent = false) : bool
    {
        if ($permanent) {
            return $this->driver->delete($this->table, $dso);
        }
        $dso['dso.deleted'] = time();
        return $this->update($dso);
    }

    public function undelete(DSOInterface &$dso) : bool
    {
        unset($dso['dso.deleted']);
        return $this->update($dso);
    }

    public function create(array $data = array()) : DSOInterface
    {
        if (!($class = $this->class($data))) {
            $class = DSO::class;
        }
        $dso = new $class($data, $this);
        $this->hook_create($dso);
        $dso->hook_create();
        $dso->resetChanges();
        return $dso;
    }

    public function createTable() : bool
    {
        return $this->driver->createTable(
            $this->table,
            ($this->driver::EXTENSIBLE_VIRTUAL_COLUMNS?$this->virtualColumns:$this::CORE_VIRTUAL_COLUMNS)
        );
    }

    protected function virtualColumnName($path) : ?string
    {
        if ($this->driver::EXTENSIBLE_VIRTUAL_COLUMNS) {
            $vcols = $this->virtualColumns;
        } else {
            $vcols = static::CORE_VIRTUAL_COLUMNS;
        }
        return @$vcols[$path]['name'];
    }

    public function update(DSOInterface &$dso) : bool
    {
        if (!$dso->changes() && !$dso->removals()) {
            return true;
        }
        $this->hook_update($dso);
        $dso->hook_update();
        $out = $this->driver->update($this->table, $dso);
        $dso->resetChanges();
        return $out;
    }

    public function search() : Search
    {
        return new Search($this);
    }

    protected function makeObjectFromRow($arr)
    {
        $data = json_decode($arr['json_data'], true);
        return $this->create($data);
    }

    protected function makeObjectsFromRows($arr)
    {
        foreach ($arr as $key => $value) {
            $arr[$key] = $this->makeObjectFromRow($value);
        }
        return $arr;
    }

    public function executeSearch(Search $search, array $params = array(), $deleted = false) : array
    {
        //add deletion clause and expand column names
        $search = $this->preprocessSearch($search, $deleted);
        //run select
        $r = $this->driver->select(
            $this->table,
            $search,
            $params
        );
        return $this->makeObjectsFromRows($r);
    }

    public function read(string $value, string $field = 'dso.id', $deleted = false) : ?DSOInterface
    {
        $search = $this->search();
        $search->where('${'.$field.'} = :value');
        if ($results = $search->execute([':value'=>$value], $deleted)) {
            return array_shift($results);
        }
        return null;
    }

    public function insert(DSOInterface &$dso) : bool
    {
        $this->hook_update($dso);
        $dso->hook_update();
        $dso->resetChanges();
        return $this->driver->insert($this->table, $dso);
    }

    protected function preprocessSearch($input, $deleted)
    {
        //clone search so we're not accidentally messing with a reference
        $search = new Search($this);
        $search->where($input->where());
        $search->order($input->order());
        /* add deletion awareness to where clause */
        if ($deleted !== null) {
            $where = $search->where();
            if ($deleted === true) {
                $added = '${dso.deleted} is not null';
            } else {
                $added = '${dso.deleted} is null';
            }
            if ($where) {
                $where = '('.$where.') AND '.$added;
            } else {
                $where = $added;
            }
            $search->where($where);
        }
        /* expand virtual column names */
        foreach (['where','order'] as $clause) {
            if ($value = $search->$clause()) {
                $value = preg_replace_callback(
                    '/\$\{([^\}\\\]+)\}/',
                    function ($matches) {
                        /* depends on whether a virtual column is expected for this value */
                        if ($vcol = $this->virtualColumnName($matches[1])) {
                            return "`$vcol`";
                        }
                        return $matches[0];
                    },
                    $value
                );
                $search->$clause($value);
            }
        }
        /* return search */
        return $search;
    }

    protected static function generate_id($chars, $length) : string
    {
        $check = new Check();
        do {
            $id = '';
            while (strlen($id) < $length) {
                $id .= substr(
                    $chars,
                    rand(0, strlen($chars)-1),
                    1
                );
            }
        } while ($check->hasProfanity($id));
        return $id;
    }
}
