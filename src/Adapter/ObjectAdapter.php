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
            $propertyType = $property->getType();

            if (!isset($data[$propertyName])) {
                continue;
            }
            $convertedValue = $value = $data[$propertyName];

            // 处理属性类型
            if ($propertyType instanceof \ReflectionNamedType) {
                $typeName = $propertyType->getName();
                if (in_array($typeName, ['int', 'string', 'float', 'bool'])) {
                    $convertedValue = $this->convertScalarValue($value, $typeName);
                } elseif (class_exists($typeName)) {
                    // 递归转换为嵌套对象
                    $convertedValue = $this->convertToObject($value, $typeName);
                } elseif ($typeName === 'array') {
                    $itemType = $this->getArrayItemType($property);
                    if (empty($itemType)) {
                        return $data;
                    }

                    if (in_array($itemType, ['int', 'string', 'float', 'bool'])) {
                        foreach ((array)$value as $item) {
                            $convertedValue[] = $this->convertScalarValue($item, $itemType);
                        }
                    } else if (class_exists($itemType)) {
                        // 转换为对象数组：每个元素都转为 itemType 实例
                        $convertedValue = [];
                        foreach ($value as $item) {
                            $convertedValue[] = $this->convertToObject($item, $itemType);
                        }
                    } else {
                        return $data;
                    }
                } else {
                    return $data;
                }
            }

            // 设置属性值（即使是私有属性也强制设置，可根据需求调整访问权限）
            $property->setAccessible(true);
            $property->setValue($object, $convertedValue);
        }

        return $object;
    }

    private function getArrayItemType(\ReflectionProperty $property): ?string
    {
        $docComment = $property->getDocComment();
        if (empty($docComment)) {
            return null;
        }

        // 匹配 @var 注释中的数组类型（如 User[] 或 array<User>）
        if (preg_match('/@var\s+([a-zA-Z0-9_\\\]+)\[\]/', $docComment, $matches)) {
            return $matches[1];
        }
        if (preg_match('/@var\s+array<([a-zA-Z0-9_\\\]+)>/', $docComment, $matches)) {
            return $matches[1];
        }

        return null;
    }
}