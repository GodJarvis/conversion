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
        if (!class_exists($targetClassName)) {
            return $data;
        }

        $reflection = new \ReflectionClass($targetClassName);
        $object = $reflection->newInstanceWithoutConstructor();
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            if (!isset($data[$propertyName])) {
                continue;
            }
            $convertedValue = $value = $data[$propertyName];
            $typeInfo = $this->parsePropertyType($property);
            $typeName = $typeInfo['type'];
            $itemType = $typeInfo['itemType'];

            // 仅处理有类型声明的属性
            if ($typeName) {
                if (is_scalar($value)) {
                    $convertedValue = $this->convertScalarValue($value, $typeName);
                } elseif (class_exists($typeName)) {
                    // 递归转换为嵌套对象
                    $convertedValue = $this->convertToObject($value, $typeName);
                }
            }

            // 处理数组类型（包括对象数组）
            if ($typeName === 'array' && $itemType) {
                $convertedValue = [];
                // 如果数组元素是对象类型
                if (class_exists($itemType)) {
                    foreach ($value as $item) {
                        $convertedValue[] = $this->convertToObject($item, $typeName);
                    }
                } else {
                    // 数组元素是基本类型
                    foreach ((array)$value as $item) {
                        $convertedValue[] = $this->convertScalarValue($item, $itemType);
                    }
                }
            }

            // 设置属性值（即使是私有属性也强制设置，可根据需求调整访问权限）
            $property->setAccessible(true);
            $property->setValue($object, $convertedValue);
        }

        return $object;
    }

    private function parsePropertyType(\ReflectionProperty $property): array
    {
        $docComment = $property->getDocComment();
        $result = ['type' => null, 'itemType' => null];

        if (empty($docComment)) {
            return $result;
        }

        // 匹配 @var 标签后的类型声明
        if (preg_match('/@var\s+([^\s]+)/', $docComment, $matches)) {
            $typeStr = $matches[1];

            // 处理数组类型：User[] 或 array<User>
            if (preg_match('/^([a-zA-Z0-9_\\\]+)\[\]$/', $typeStr, $arrayMatches)) {
                $result['type'] = 'array';
                $result['itemType'] = $arrayMatches[1];
            } elseif (preg_match('/^array<([a-zA-Z0-9_\\\]+)>$/', $typeStr, $arrayMatches)) {
                $result['type'] = 'array';
                $result['itemType'] = $arrayMatches[1];
            } else {
                // 基本类型或对象类型
                $result['type'] = $typeStr;
            }
        }

        return $result;
    }
}