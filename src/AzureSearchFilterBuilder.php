<?php

namespace WeLabs\AzureScout;

use Laravel\Scout\Builder;

class AzureSearchFilterBuilder
{
    public function build(Builder $builder): string
    {
        $filters = [];
        $operatorMap = [
            '='      => 'eq',
            '!='     => 'ne',
            '<>'     => 'ne',
            '>'      => 'gt',
            '>='     => 'ge',
            '<'      => 'lt',
            '<='     => 'le',
            'like'   => 'search.ismatch',
            'startswith' => 'search.ismatch',
            'endswith'   => 'search.ismatch',
            'contains' => 'search.ismatch',
        ];

        // Scalar and advanced wheres (key => value or ['type'=>...])
        foreach ($builder->wheres as $key => $value) {
            if (is_array($value) && isset($value['type'])) {
                $filters[] = $this->parseTypedWhere($value, $operatorMap);
            } elseif (is_bool($value)) {
                $filters[] = sprintf("%s eq %s", $key, $value ? 'true' : 'false');
            } elseif (is_null($value)) {
                $filters[] = sprintf("%s eq null", $key);
            } elseif (is_numeric($value)) {
                $filters[] = sprintf("%s eq %s", $key, $value);
            } elseif (is_numeric($key)) { // Raw where query
                $filters[] = $value;
            } else {
                $escaped = str_replace("'", "\\'", $value);
                $filters[] = sprintf("%s eq '%s'", $key, $escaped);
            }
        }

        // whereIns (IN)
        if (property_exists($builder, 'whereIns')) {
            foreach ($builder->whereIns as $key => $values) {
                $escaped = array_map(fn($v) => is_string($v) ? str_replace("'", "\\'", $v) : $v, $values);
                $formatted = implode(',', $escaped);
                $filters[] = "search.in($key, '$formatted', ',')";
            }
        }

        // whereNotIns (NOT IN)
        if (property_exists($builder, 'whereNotIns')) {
            foreach ($builder->whereNotIns as $key => $values) {
                $escaped = array_map(fn($v) => is_string($v) ? str_replace("'", "\\'", $v) : $v, $values);
                $formatted = implode(',', $escaped);
                $filters[] = "not search.in($key, '$formatted', ',')";
            }
        }

        // Compose with 'and' as default logical operator
        $filter = implode(' and ', array_filter($filters));
        return $filter;
    }
    
    protected function parseTypedWhere(array $where, array $operatorMap): string
    {
        // Remove the all spaces from the type and convert to lowercase
        $type = str_replace(' ', '', strtolower($where['type']));
        
        $column = $where['column'] ?? $where['field'] ?? null;
        $value = $where['value'] ?? null;
    
        switch ($type) {
            case 'basic':
                $operator = strtolower($where['operator'] ?? '=');
                return $this->formatBasicFilter($column, $operator, $value, $operatorMap);
    
            case 'like':
            case 'starts':
            case 'startswith':
            case 'ends':
            case 'endswith':
            case 'contains':
                return $this->formatBasicFilter($column, strtolower($type), $value, $operatorMap);
    
            case 'null':
                return "$column eq null";
    
            case 'notnull':
                return "$column ne null";
    
            case 'in':
                return $this->formatInFilter($column, $value);
    
            case 'notin':
                return "not " . $this->formatInFilter($column, $value);
    
            case 'boolean':
                return "$column eq " . ($value ? 'true' : 'false');
    
            case 'raw':
                $condition =  $where['sql'] ?? ($where['condition'] ?? '');
                return str_replace(array_keys($operatorMap), array_values($operatorMap), $condition);
    
            case 'group':
                $nested = array_map(function ($nestedWhere) use ($operatorMap) {
                    return $this->parseTypedWhere($nestedWhere, $operatorMap);
                }, $where['conditions'] ?? []);
                $glue = strtolower($where['boolean'] ?? 'and');
                return '(' . implode(" $glue ", array_filter($nested)) . ')';
    
            default:
                return '';
        }
    }
    
    protected function formatBasicFilter(string $column, string $operator, $value, array $operatorMap): string
    {
        $mapped = $operatorMap[$operator] ?? $operator;
    
        if (is_null($value)) {
            return "$column eq null";
        }

        switch ($operator) {
            case 'startswith':
                $value = $value . '*';
                break;
            case 'endswith':
                $value = '*' . $value;
                break;
        }
        
        if (in_array($operator, ['contains', 'startswith', 'endswith', 'like'])) {
            // dd($mapped, $operator, $operatorMap, $value);
            $escaped = is_string($value) ? str_replace("'", "\\'", $value) : (string)$value;
            return "$mapped('$escaped', '$column')";
        }
    
        if (is_bool($value)) {
            return "$column $mapped " . ($value ? 'true' : 'false');
        }
    
        if (is_string($value)) {
            $value = str_replace("'", "\\'", $value); // escape single quotes
            return "$column $mapped '$value'";
        }
    
        return "$column $mapped $value";
    }
    
    protected function formatInFilter(string $column, array $values): string
    {
        $formatted = implode(',', array_map(function ($v) {
            return is_string($v) ? str_replace("'", "\\'", $v) : $v;
        }, $values));
    
        return "search.in($column, '$formatted', ',')";
    }
}