<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr;

use Destructr\DSOFactoryInterface;
use Destructr\Drivers\DSODriverInterface;

class Search implements \Serializable
{
    protected $factory;
    protected $where;
    protected $order;
    protected $limit;
    protected $offset;

    public function __construct(DSOFactoryInterface $factory=null)
    {
        $this->factory = $factory;
    }

    public function quote(string $str) : string
    {
        return $this->factory->quote($str);
    }

    public function count(array $params = array(), $deleted = false)
    {
        return $this->factory->executeCount($this, $params, $deleted);
    }

    public function execute(array $params = array(), $deleted = false)
    {
        return $this->factory->executeSearch($this, $params, $deleted);
    }

    public function where(string $set = null) : ?string
    {
        if ($set !== null) {
            $this->where = $set;
        }
        return $this->where;
    }

    public function order(string $set = null) : ?string
    {
        if ($set !== null) {
            $this->order = $set;
        }
        return $this->order;
    }

    public function limit(int $set = null) : ?int
    {
        if ($set !== null) {
            $this->limit = $set;
        }
        return $this->limit;
    }

    public function offset(int $set = null) : ?int
    {
        if ($set !== null) {
            $this->offset = $set;
        }
        return $this->offset;
    }

    public function serialize()
    {
        return json_encode(
            [$this->where(),$this->order(),$this->limit(),$this->offset()]
        );
    }

    public function unserialize($string)
    {
        list($where, $order, $limit, $offset) = json_decode($string, true);
        $this->where($where);
        $this->order($order);
        $this->limit($limit);
        $this->offset($offset);
    }
}
