<?php
/**
 * Created by : PhpStorm
 * User: godjarvis
 * Date: 2025/8/4
 * Time: 10:33
 */

namespace GodJarvis\Conversion\Adapter;

class ObjectAdapter extends AbstractAdapter
{
    protected function format($format)
    {
        $rawData = $this->getRawData();
        if (empty($format) ||
            !is_string($format) ||
            !class_exists($format) ||
            !is_array($rawData)
        ) {
            return $rawData;
        }

        $converted = $this->convertToObject($rawData, $format);
        if (empty($converted)) {
            return $rawData;
        }
        return $converted;
    }

    private function convertToObject(array $data, string $targetClassName)
    {
        $object = new $targetClassName();
        $reflection = new \ReflectionClass($targetClassName);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            // 获取属性声明的类型（如 int、string 等）
            $type = $property->getType();
            if (!$type) {
                continue;
            }
            $typeName = $type->getName();

            if (!isset($data[$propertyName])) {
                continue;
            }
            $value = $data[$propertyName];

            // 赋值到对象属性
            $object->$propertyName = $this->convertValue($value, $typeName);
        }

        return $object;
    }
}