<?php

namespace WeLabs\AzureScout;

use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Contracts\UpdatesIndexSettings;

class AzureSearchEngine extends Engine implements UpdatesIndexSettings
{
    protected $searchClient;
    protected $softDelete;

    public function __construct(AzureSearchClient $searchClient, bool $softDelete = false)
    {
        $this->searchClient = $searchClient ?: new AzureSearchClient(config('scout.azure.endpoint'), config('scout.azure.api_key'));
        $this->softDelete = $softDelete;
    }

    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }
        $index = $models->first()->searchableAs();
        $documents = $models->map(function ($model) {
            $data = $model->toSearchableArray();
            if (empty($data)) return null;
            $data['@search.action'] = 'mergeOrUpload';
            $data[$model->getScoutKeyName()] = $model->getScoutKey();
            return $data;
        })->filter()->values()->all();
        if (!empty($documents)) {
            $this->searchClient->uploadDocuments($index, $documents);
        }
    }

    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }
        $index = $models->first()->searchableAs();
        $documents = $models->map(function ($model) {
            return [
                '@search.action' => 'delete',
                $model->getScoutKeyName() => $model->getScoutKey(),
            ];
        })->all();
        $this->searchClient->uploadDocuments($index, $documents);
    }

    public function search(Builder $builder)
    {
        $index = $builder->index ?: $builder->model->searchableAs();
        $query = [
            'search' => $builder->query,
            'top' => $builder->limit,
        ];
        if (!empty($builder->wheres)) {
            $query['filter'] = $this->filters($builder);
        }
        if (!empty($builder->orders)) {
            $query['orderby'] = $this->buildSortFromOrderByClauses($builder);
        }
        $query = array_merge($builder->options, $query);
        return $this->searchClient->search($index, $query);
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $index = $builder->index ?: $builder->model->searchableAs();
        $query = [
            'search' => $builder->query,
            'top' => (int) $perPage,
            'skip' => (int) $perPage * ($page - 1),
        ];
        if (!empty($builder->wheres)) {
            $query['filter'] = $this->filters($builder);
        }
        if (!empty($builder->orders)) {
            $query['orderby'] = $this->buildSortFromOrderByClauses($builder);
        }
        $query = array_merge($builder->options, $query);
        return $this->searchClient->search($index, $query);
    }

    public function map($builder, $results, $model)
    {
        if (empty($results['value'])) {
            return collect();
        }
        $keys = collect($results['value'])->pluck($model->getScoutKeyName())->all();
        return $model->whereIn($model->getScoutKeyName(), $keys)->get();
    }

    public function mapIds($results)
    {
        if (empty($results['value'])) {
            return collect();
        }
        return collect($results['value'])->pluck('id');
    }

    public function getTotalCount($results)
    {
        return $results['@odata.count'] ?? 0;
    }

    public function flush($model)
    {
        $this->searchClient->deleteIndex($model->searchableAs());
        $this->createIndex($model->searchableAs());
        $this->createIndex(get_class($model));
    }

    public function lazyMap($builder, $results, $model)
    {
        return $this->map($builder, $results, $model);
    }

    /**
     * @see https://laravel.com/docs/11.x/scout#creating-indexes
     *
     * @param string $name
     * @param array $options
     * @return array||void
     */
    public function createIndex($name, array $options = [])
    {
        $settings = config('scout.azure.index-settings.'.$name);

        if (!empty($settings) && is_array($settings)) {
            if (class_exists($name)) {
                $name = app()->make($name)->searchableAs();
            }
            return $this->updateIndexSettings($name, $settings);
        }
    }

    public function deleteIndex($name)
    {
        return $this->searchClient->deleteIndex($name);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/searchservice/data-sources/create-or-update?view=rest-searchservice-2024-07-01&tabs=HTTP
     *
     * @param string $name
     * @param array $settings
     * @return array
     */
    public function updateIndexSettings(string $name, array $settings = [])
    {

        if (empty($settings)) {
            throw new \Exception('Settings are required');
        }

        $response = $this->searchClient->createOrUpdateIndex($name, $settings);

        return $response;
    }

    protected function filters(Builder $builder)
    {
        return app(AzureSearchFilterBuilder::class)->build($builder);
    }

    protected function buildSortFromOrderByClauses(Builder $builder)
    {
        return collect($builder->orders)->map(function ($order) {
            return $order['column'] . ' ' . $order['direction'];
        })->implode(', ');
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/searchservice/data-sources/create?view=rest-searchservice-2024-07-01&tabs=HTTP#softdeletecolumndeletiondetectionpolicy
     * 
     * Laravel Scout expects this for engines that implement UpdatesIndexSettings.
     * Azure Search does not support native soft delete filtering at the engine level,
     */
    public function configureSoftDeleteFilter(array $settings = [])
    {
        $softDeleteColumn = config('scout.soft_delete_column', 'isDeleted');
        $softDeleteValue = config('scout.soft_delete_value', now());

        $settings['dataDeletionDetectionPolicy'] = [
            '@odata.type' => '#Microsoft.Azure.Search.SoftDeleteColumnDeletionDetectionPolicy',
            'softDeleteColumnName' => $softDeleteColumn,
            'softDeleteMarkerValue' => $softDeleteValue,
        ];

        return $settings;
    }
} 