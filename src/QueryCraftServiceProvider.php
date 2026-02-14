<?php

namespace Romdh4ne\QueryCraft;

use Illuminate\Support\ServiceProvider;
use Romdh4ne\QueryCraft\Commands\AnalyzeCommand;
use Romdh4ne\QueryCraft\Services\QueryAnalysisService;

class QueryCraftServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyzeCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__ . '/../config/querycraft.php' => config_path('querycraft.php'),
            ], 'querycraft-config');
        }

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'querycraft');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../resources/css' => public_path('vendor/querycraft/css'),
            __DIR__ . '/../resources/js' => public_path('vendor/querycraft/js'),
        ], 'querycraft-assets');
    }

    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/querycraft.php',
            'querycraft'
        );

        $this->app->singleton(QueryAnalysisService::class, function ($app) {
            return new QueryAnalysisService();
        });
    }
}