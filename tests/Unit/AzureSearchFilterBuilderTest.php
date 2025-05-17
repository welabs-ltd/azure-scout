<?php

namespace WeLabs\AzureScout\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Laravel\Scout\Builder;
use WeLabs\AzureScout\AzureSearchFilterBuilder;

class AzureSearchFilterBuilderTest extends TestCase
{
    protected AzureSearchFilterBuilder $filterBuilder;
    protected Builder $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filterBuilder = new AzureSearchFilterBuilder();
        $this->queryBuilder = Mockery::mock(Builder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_builds_basic_equality_filter()
    {
        $wheres = [
            ['type' => 'Basic', 'column' => 'name', 'operator' => '=', 'value' => 'Test']
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("name eq 'Test'", $result);
    }

    /** @test */
    public function it_builds_comparison_filters()
    {
        $wheres = [
            ['type' => 'Basic', 'column' => 'age', 'operator' => '>', 'value' => 18],
            ['type' => 'Basic', 'column' => 'price', 'operator' => '<=', 'value' => 100]
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("age gt 18 and price le 100", $result);
    }

    /** @test */
    public function it_builds_like_filters()
    {
        $wheres = [
            ['type' => 'Like', 'column' => 'description', 'value' => 'test']
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("search.ismatch('test', 'description')", $result);
    }

    /** @test */
    public function it_builds_in_filters()
    {
        $wheres = [
            ['type' => 'In', 'column' => 'status', 'value' => ['active', 'pending']]
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("search.in(status, 'active,pending', ',')", $result);
    }

    /** @test */
    public function it_builds_not_in_filters()
    {
        $wheres = [
            ['type' => 'NotIn', 'column' => 'category', 'value' => ['deleted', 'archived']]
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("not search.in(category, 'deleted,archived', ',')", $result);
    }

    /** @test */
    public function it_builds_null_filters()
    {
        $wheres = [
            ['type' => 'Null', 'column' => 'deleted_at']
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("deleted_at eq null", $result);
    }

    /** @test */
    public function it_builds_boolean_filters()
    {
        $wheres = [
            ['type' => 'Boolean', 'column' => 'is_active', 'value' => true]
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("is_active eq true", $result);
    }

    /** @test */
    public function it_builds_group_filters()
    {
        $wheres = [
            [
                'type' => 'Group',
                'boolean' => 'and',
                'conditions' => [
                    ['type' => 'Basic', 'column' => 'age', 'operator' => '>=', 'value' => 18],
                    ['type' => 'Basic', 'column' => 'age', 'operator' => '<=', 'value' => 65]
                ]
            ]
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("(age ge 18 and age le 65)", $result);
    }

    /** @test */
    public function it_handles_special_characters_in_string_values()
    {
        $wheres = [
            ['type' => 'Basic', 'column' => 'name', 'operator' => '=', 'value' => "O'Connor"]
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("name eq 'O\'Connor'", $result);
    }

    /** @test */
    public function it_builds_starts_with_filters()
    {
        $wheres = [
            ['type' => 'StartsWith', 'column' => 'name', 'value' => 'Dr.']
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("search.ismatch('Dr.*', 'name')", $result);
    }

    /** @test */
    public function it_builds_ends_with_filters()
    {
        $wheres = [
            ['type' => 'EndsWith', 'column' => 'email', 'value' => '@example.com']
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("search.ismatch('*@example.com', 'email')", $result);
    }

    /** @test */
    public function it_builds_raw_filters()
    {
        $wheres = [
            ['type' => 'Raw', 'sql' => "name eq 'John'"]
        ];

        $this->queryBuilder->wheres = $wheres;

        $result = $this->filterBuilder->build($this->queryBuilder);
        $this->assertSame("name eq 'John'", $result);
    }
} 