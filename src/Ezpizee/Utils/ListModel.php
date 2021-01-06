<?php

namespace Ezpizee\Utils;

use Countable;
use JsonSerializable;

class ListModel implements JsonSerializable, Countable
{
    private $valueType = null;
    private $data = array();

    function __construct($val)
    {
        $this->reset($val);
    }

    public function reset($v = null)
    {
        if (is_array($v)) {
            $this->data = $v;
            $this->valueType = 'array';
        }
        else if (is_object($v)) {
            $this->data = json_decode(json_encode($v), true);
            $this->valueType = 'object';
        }
        else if (pathinfo($v, PATHINFO_EXTENSION) && file_exists($v)) {
            $this->data = json_decode(file_get_contents($v), true);
            $this->valueType = 'file';
        }
        else {
            $this->data = empty($v) ? array() : array($v);
            $this->valueType = 'string';
        }
    }

    public function containsKey($key)
    : bool
    {
        return $this->has($key);
    }

    public function has($k)
    : bool
    {
        return $k !== null && isset($this->data[$k]);
    }

    public function containsValue($value)
    : bool
    {
        foreach ($this->data as $val) {
            if ($val === $value) {
                return true;
            }
        }
        return false;
    }

    public function put(string $k, $v)
    {
        $this->set($k, $v);
    }

    public function set(string $k, $v)
    {
        if ($k !== null) {
            $this->data[$k] = $v;
        }
    }

    public function search(string $key)
    {
        return ArrayUtil::search($key, $this->data);
    }

    public function merge(array $list)
    {
        foreach ($list as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    public function get($k = null, $default = null)
    {
        if ($k !== null && isset($this->data[$k])) {
            return $this->data[$k];
        }
        else if ($k === null && $this->valueType === 'string') {
            return end($this->data);
        }
        return $default;
    }

    public function remove()
    {
        $argc = func_get_args();
        if ($argc != null && sizeof($argc)) {
            foreach ($argc as $k) {
                if (isset($this->data[$k]) || $this->data[$k] === null) {
                    unset($this->data[$k]);
                }
            }
        }
    }

    public function is($k, $v)
    : bool
    {
        return $k !== null && isset($this->data[$k]) && $this->data[$k] === $v;
    }

    public function first()
    {
        if (sizeof($this->data)) {
            return array_values($this->data)[0];
        }
        return null;
    }

    public function last()
    {
        if (sizeof($this->data)) {
            $arr = array_values($this->data);
            return end($arr);
        }
        return null;
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function isEmpty()
    : bool
    {
        return !$this->hasElement();
    }

    public function hasElement()
    : bool
    {
        return $this->count() > 0;
    }

    public function count()
    : int
    {
        return sizeof($this->data);
    }

    public function getAsArray()
    : array
    {
        return $this->data;
    }

    public function __toString()
    : string
    {
        return json_encode($this->data);
    }
}
