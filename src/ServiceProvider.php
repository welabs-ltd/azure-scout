<?php

namespace WeLabs\AzureScout;

use Illuminate\Support\ServiceProvider as LaravelProvider;
use Laravel\Scout\EngineManager;

class ServiceProvider extends LaravelProvider
{
    protected $rootPath;

    public function register()
    {
        $this->rootPath = realpath(__DIR__.'/../');
        $this->app->singleton(AzureSearchClient::class, function ($app) {
            $config = $app['config']['scout.azure'];
            return new AzureSearchClient(
                $config['endpoint'],
                $config['api_key']
            );
        });
    }

    public function boot()
    {
        
        resolve(EngineManager::class)->extend('azure', function ($app) {
            return new AzureSearchEngine(
                $app->make(AzureSearchClient::class),
                config('scout.soft_delete', false)
            );
        });
    }
} 