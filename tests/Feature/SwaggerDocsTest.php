<?php

namespace Mtrajano\LaravelSwagger\Tests\Feature;

use Mtrajano\LaravelSwagger\Tests\TestCase;

class SwaggerDocsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->setBasePath(__DIR__ . '/../..');
    }

    public function testGetSwaggerUiUsingDefaultVersion()
    {
        $defaultVersion = config('laravel-swagger.defaultVersion');

        $route = route(
            config('laravel-swagger.route.name'),
            $defaultVersion,
            false
        );

        $filePath = 'swagger-'.str_replace('.', '-', $defaultVersion).'.json';

        $this->get($route)
            ->assertSuccessful()
            ->assertViewIs('laravel-swagger::index')
            ->assertViewHas('filePath', config('app.url').'/'.$filePath);
    }

    public function testGetSwaggerUi()
    {
        foreach(config('laravel-swagger.versions') as $version) {
            $route = route(config('laravel-swagger.route.name'), $version['appVersion'], false);
            $this->get($route)
                ->assertSuccessful()
                ->assertViewIs('laravel-swagger::index')
                ->assertViewHas(
                    'filePath',
                    config('app.url').'/'.$version['file_path']
                );
        }
    }
}