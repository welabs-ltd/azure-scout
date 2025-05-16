<?php

namespace App\Search;

use Illuminate\Database\Eloquent\Builder;

class AzureSearchFilterBuilder
{
    protected array $operatorMap = [
        '='      => 'eq',
        '!='     => 'ne',
        '<>'     => 'ne',
        '>'      => 'gt',
        '>='     => 'ge',
        '<'      => 'lt',
        '<='     => 'le',
        'like'   => 'contains',
        'starts' => 'startswith',
        'ends'   => 'endswith',
    ];

    public function build(Builder $builder): string
    {
        $filters = [];

        foreach ($builder->wheres as $where) {
            if (is_array($where) && isset($where['type'])) {
                $filters[] = $this->parseTypedWhere($where);
            } elseif (is_array($where)) {
                foreach ($where as $column => $value) {
                    $filters[] = $this->formatBasicFilter($column, 'eq', $value);
                }
            } else {
                foreach ($builder->wheres as $column => $value) {
                    $filters[] = $this->formatBasicFilter($column, 'eq', $value);
                }
                break;
            }
        }

        return implode(' and ', array_filter($filters));
    }

    protected function parseTypedWhere(array $where): string
    {
        $type = $where['type'];
        $column = $where['column'] ?? $where['field'] ?? null;
        $value = $where['value'] ?? null;

        switch ($type) {
            case 'Basic':
                $operator = strtolower($where['operator'] ?? '=');
                return $this->formatBasicFilter($column, $operator, $value);

            case 'Like':
            case 'StartsWith':
            case 'EndsWith':
                return $this->formatBasicFilter($column, strtolower($type), $value);

            case 'Null':
                return "$column eq null";

            case 'NotNull':
                return "$column ne null";

            case 'In':
                return $this->formatInFilter($column, $value);

            case 'NotIn':
                return "not " . $this->formatInFilter($column, $value);

            case 'Boolean':
                return "$column eq " . ($value ? 'true' : 'false');

            case 'Raw':
                return str_replace(array_keys($this->operatorMap), array_values($this->operatorMap), $where['sql']);

            case 'Group':
                $nested = array_map(fn($w) => $this->parseTypedWhere($w), $where['conditions'] ?? []);
                $glue = strtolower($where['boolean'] ?? 'and');
                return '(' . implode(" $glue ", array_filter($nested)) . ')';

            default:
                return '';
        }
    }

    protected function formatBasicFilter(string $column, string $operator, $value): string
    {
        $mapped = $this->operatorMap[$operator] ?? $operator;

        if (is_null($value)) {
            return "$column eq null";
        }

        if (in_array($mapped, ['contains', 'startswith', 'endswith'])) {
            $escaped = is_string($value) ? str_replace("'", "''", $value) : (string)$value;
            return "$mapped($column, '$escaped')";
        }

        if (is_bool($value)) {
            return "$column $mapped " . ($value ? 'true' : 'false');
        }

        if (is_string($value)) {
            $value = str_replace("'", "''", $value);
            return "$column $mapped '$value'";
        }

        return "$column $mapped $value";
    }

    protected function formatInFilter(string $column, array $values): string
    {
        $formatted = implode(',', array_map(function ($v) {
            return is_string($v) ? str_replace("'", "''", $v) : $v;
        }, $values));

        return "search.in($column, '$formatted', ',')";
    }
}