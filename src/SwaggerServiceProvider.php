<?php

namespace Mtrajano\LaravelSwagger;

use Illuminate\Support\ServiceProvider;
use Mtrajano\LaravelSwagger\Console\GenerateSwaggerDocCommand;

class SwaggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(SwaggerDocsManager::class, function ($app) {
            return new SwaggerDocsManager($app['config']['laravel-swagger']);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSwaggerDocCommand::class,
            ]);
        }

        $source = __DIR__ . '/../config/laravel-swagger.php';

        $this->publishes([
            $source => config_path('laravel-swagger.php'),
        ]);

        $this->mergeConfigFrom(
            $source, 'laravel-swagger'
        );

        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-swagger');
    }
}
