<?php
/**
 * Created by : PhpStorm
 * User: godjarvis
 * Date: 2025/8/4
 * Time: 10:33
 */

namespace GodJarvis\Conversion\Adapter;

class JsonSchemaAdapter extends AbstractAdapter
{
    public function format($format): array
    {
        $rawData = $this->getRawData();
        if (empty($format) || !is_array($rawData)) {
            return $rawData;
        }

        $schema = [];
        if (is_string($format)) {
            $schema = json_decode($format, true);
        } else if (is_array($format)) {
            $schema = $format;
        }

        if (empty($schema)) {
            return $rawData;
        }

        $converted = $this->convertBySchema($rawData, $format);
        if (empty($converted)) {
            return $rawData;
        }
        return $converted;
    }

    public function convertBySchema($data, array $schema)
    {
        // 处理对象类型
        if ($schema['type'] === 'object' && isset($schema['properties'])) {
            $converted = [];
            foreach ($schema['properties'] as $field => $fieldSchema) {
                if (isset($data[$field])) {
                    $converted[$field] = $this->convertBySchema($data[$field], $fieldSchema);
                }
            }
            return $converted;
        }

        // 处理数组类型
        if ($schema['type'] === 'array' && isset($schema['items'])) {
            $converted = [];
            foreach ((array)$data as $item) {
                // 递归转换数组元素
                $converted[] = $this->convertBySchema($item, $schema['items']);
            }
            return $converted;
        }

        // 基础类型转换（根据schema的type转换）
        return $this->convertValue($data, $schema['type']);
    }
}