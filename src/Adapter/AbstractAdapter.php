<?php
/**
 * Created by : PhpStorm
 * User: godjarvis
 * Date: 2025/8/4
 * Time: 11:01
 */

namespace GodJarvis\Conversion\Adapter;

abstract class AbstractAdapter
{
    private $data;

    public function __construct($data)
    {
        $this->data = $this->tryConvertToArray($data);
    }

    public function getRawData()
    {
        return $this->data;
    }

    public function tryConvertToArray($obj)
    {
        $data = [];
        if (is_object($obj)) {
            $data = get_object_vars($obj);
        } elseif (is_array($obj)) {
            $data = $obj;
        }

        if (empty($data)) {
            return $obj;
        }

        return array_map(function ($val) {
            return (is_array($val) || is_object($val)) ? $this->tryConvertToArray($val) : $val;
        }, $data);
    }

    public function convertValue($value, $type)
    {
        switch (strtolower($type)) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'string':
                return (string)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'number':
                return is_numeric($value) ? (float)$value : $value;
            case 'bool':
            case 'boolean':
                return (bool)filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            default:
                return $value;
        }
    }

    abstract public function format($format);
}