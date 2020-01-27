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
        $config = [
            'defaultVersion' => '1.0.0',
            'title' => env('APP_NAME'),
            'description' => '',
            'route' => [
                'path' => '/docs/{version?}',
                'name' => 'laravel-swagger.docs',
                'middleware' => [],
            ],
            'versions' => [
                [
                    'appVersion' => '1.0.0',
                    'host' => env('APP_URL'),
                    'basePath' => '/v1',
                    'schemes' => [],
                    'consumes' => [],
                    'produces' => [],
                    'ignoredMethods' => [
                        'head',
                    ],
                    'ignoredRoutes' => [
                        'laravel-swagger.docs',
                        'laravel-swagger.asset'
                    ],
                    'parseDocBlock' => true,
                    'parseSecurity' => true,
                    'authFlow' => 'accessCode',
                    'file_path' => env('SWAGGER_FILE_PATH', 'swagger-1.0.0.json'),
                ],
                [
                    'appVersion' => '2.0.0',
                    'host' => env('APP_URL'),
                    'basePath' => '/v2',
                    'schemes' => [],
                    'consumes' => [],
                    'produces' => [],
                    'ignoredMethods' => [
                        'head',
                    ],
                    'ignoredRoutes' => [
                        'laravel-swagger.docs',
                        'laravel-swagger.asset'
                    ],
                    'parseDocBlock' => true,
                    'parseSecurity' => true,
                    'authFlow' => 'accessCode',
                    'file_path' => env('SWAGGER_FILE_PATH', 'swagger-2.0.0.json'),
                ],
            ],
        ];

        config(['laravel-swagger' => $config]);

        $defaultVersion = config('laravel-swagger.defaultVersion');

        $route = route(
            config('laravel-swagger.route.name'),
            $defaultVersion,
            false
        );

        $filePath = "swagger-$defaultVersion.json";

        $apiVersions = [
            '/docs/1.0.0' => "1.0.0",
            '/docs/2.0.0' => "2.0.0",
        ];

        $this->get($route)
            ->assertSuccessful()
            ->assertViewIs('laravel-swagger::index')
            ->assertViewHas('filePath', config('app.url').'/'.$filePath)
            ->assertViewHas('apiVersions', $apiVersions)
            ->assertViewHas('currentVersion', $defaultVersion);
    }

    public function testGetSwaggerUi()
    {
        foreach(config('laravel-swagger.versions') as $version) {
            $route = route(
                config('laravel-swagger.route.name'),
                $version['appVersion'],
                false
            );

            $this->get($route)
                ->assertSuccessful()
                ->assertViewIs('laravel-swagger::index')
                ->assertViewHas(
                    'filePath',
                    config('app.url')."/swagger-{$version['appVersion']}.json"
                )
                ->assertViewHas('currentVersion', $version['appVersion']);
        }
    }
}