<?php

namespace Mtrajano\LaravelSwagger\Tests\Feature;

use Mtrajano\LaravelSwagger\Tests\TestCase;

class SwaggerDocsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->setBasePath(__DIR__ . '/../..');
    }

    public function testGetSwaggerUi()
    {
        $this->get(config('laravel-swagger.routes.docs.path'))
            ->assertSuccessful()
            ->assertViewIs('laravel-swagger::index')
            ->assertViewHas('filePath', config('app.url').'/'.config('laravel-swagger.file_path'));
    }
}