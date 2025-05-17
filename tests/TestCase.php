<?php

namespace WeLabs\AzureScout\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \WeLabs\AzureScout\AzureScoutServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
} 