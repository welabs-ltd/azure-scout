<?php

namespace WeLabs\AzureScout\Tests;

use WeLabs\AzureScout\AzureSearchEngine;
use WeLabs\AzureScout\AzureSearchClient;
use Laravel\Scout\Builder;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AzureSearchEngineTest extends TestCase
{
    public function testUpdateIndexesDocuments()
    {
        $mockClient = $this->createMock(AzureSearchClient::class);
        $mockClient->expects($this->once())
            ->method('uploadDocuments')
            ->with('products', $this->callback(function ($docs) {
                return $docs[0]['@search.action'] === 'mergeOrUpload';
            }))
            ->willReturn(['result' => 'ok']);

        $engine = new AzureSearchEngine($mockClient);
        $model = new TestProduct(['id' => 1, 'name' => 'Test', 'pack_size' => '10', 'code' => 'C1', 'strength' => 'S', 'dosage' => 'D', 'pack_qty' => '5', 'category' => 'Medicine', 'sales_price' => 100, 'company_id' => '1', 'generic_name' => 'G', 'is_active' => true]);
        $engine->update(collect([$model]));
    }

    public function testDeleteRemovesDocuments()
    {
        $mockClient = $this->createMock(AzureSearchClient::class);
        $mockClient->expects($this->once())
            ->method('uploadDocuments')
            ->with('products', $this->callback(function ($docs) {
                return $docs[0]['@search.action'] === 'delete';
            }))
            ->willReturn(['result' => 'ok']);

        $engine = new AzureSearchEngine($mockClient);
        $model = new TestProduct(['id' => 1]);
        $engine->delete(collect([$model]));
    }

    public function testSearchReturnsResults()
    {
        $mockClient = $this->createMock(AzureSearchClient::class);
        $mockClient->expects($this->once())
            ->method('search')
            ->willReturn(['value' => [['id' => 1, 'name' => 'Test']]]);

        $engine = new AzureSearchEngine($mockClient);
        $builder = new Builder(new TestProduct(), 'test');
        $results = $engine->search($builder);
        $this->assertArrayHasKey('value', $results);
    }
}

class TestProduct extends Model
{
    protected $table = 'products';
    protected $guarded = [];
    public function searchableAs() { return 'products'; }
    public function getScoutKeyName() { return 'id'; }
    public function getScoutKey() { return $this->id; }
    public function toSearchableArray() {
        return [
            'id' => $this->id,
            'name' => $this->name ?? 'Test',
            'pack_size' => $this->pack_size ?? '10',
            'code' => $this->code ?? 'C1',
            'strength' => $this->strength ?? 'S',
            'dosage' => $this->dosage ?? 'D',
            'pack_qty' => $this->pack_qty ?? '5',
            'category' => $this->category ?? 'Medicine',
            'sales_price' => $this->sales_price ?? 100,
            'company_id' => $this->company_id ?? '1',
            'generic_name' => $this->generic_name ?? 'G',
            'is_active' => $this->is_active ?? true,
        ];
    }
} 