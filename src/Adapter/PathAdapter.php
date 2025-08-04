<?php
/**
 * Created by : PhpStorm
 * User: godjarvis
 * Date: 2025/8/4
 * Time: 10:33
 */

namespace GodJarvis\Conversion\Adapter;

class PathAdapter extends AbstractAdapter
{
    public function format($format): array
    {
        $rawData = $this->getRawData();
        if (empty($format) ||
            !is_array($format) ||
            !is_array($rawData)
        ) {
            return $rawData;
        }
        $this->convertByPath($rawData, $format);
        return $rawData;
    }

    public function convertByPath(array &$data, array $typeMap)
    {
        foreach ($typeMap as $path => $targetType) {
            $pathParts = explode('.', $path);
            $matchingValues = $this->findMatchingValues($data, $pathParts);
            if (empty($matchingValues)) {
                continue;
            }

            foreach ($matchingValues as &$value) {
                $value = $this->convertValue($value, $targetType);
            }
            unset($value);
        }
    }

    /**
     * 递归查找所有匹配路径的元素引用（支持通配符*）
     * @param array $pathParts 路径片段数组（如 ["b", "*", "c"]）
     * @param int $currentIndex 当前处理的路径索引
     * @return array 所有匹配元素的引用数组
     * @noinspection SlowArrayOperationsInLoopInspection
     */
    public function findMatchingValues(&$data, array $pathParts, int $currentIndex = 0)
    {
        $currentPart = $pathParts[$currentIndex];
        $isLastPart = $currentIndex === count($pathParts) - 1;

        $matches = [];
        if ($currentPart === '*') {
            if (!is_array($data)) {
                return $matches;
            }

            foreach ($data as &$item) {
                if ($isLastPart) {
                    $matches[] = &$item;
                } else {
                    $subMatches = $this->findMatchingValues($item, $pathParts, $currentIndex + 1);
                    $matches = array_merge($matches, $subMatches);
                }
            }
            unset($item);
            return $matches;
        }

        if (isset($data[$currentPart])) {
            if ($isLastPart) {
                $matches[] = &$data[$currentPart];
            } else {
                $subMatches = $this->findMatchingValues($data[$currentPart], $pathParts, $currentIndex + 1);
                $matches = array_merge($matches, $subMatches);
            }
            return $matches;
        }

        return $matches;
    }
}