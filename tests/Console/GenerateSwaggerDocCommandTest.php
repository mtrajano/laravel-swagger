<?php

namespace Mtrajano\LaravelSwagger\Tests\Console;

use Mtrajano\LaravelSwagger\SwaggerDocsManager;
use Mtrajano\LaravelSwagger\Tests\TestCase;

class GenerateSwaggerDocCommandTest extends TestCase
{
    private $defaultVersionConfig;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['router']->prefix('v1')->group(function () use ($app) {
            $app['router']
                ->get('/customers', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@index')
                ->name('customers.index')
                ->middleware('auth');
            $app['router']
                ->post('/customers', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@store')
                ->name('customers.store');
        });

        $app['router']->prefix('v2')->group(function () use ($app) {
            $app['router']
                ->put('/customers/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@update')
                ->name('customers.update');
            $app['router']
                ->delete('/customers/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@destroy')
                ->name('customers.destroy');
        });
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultVersionConfig = (
            new SwaggerDocsManager(config('laravel-swagger'))
        )->getDefaultVersionConfig();
    }

    public function testGenerateSwaggerDocToAllVersion()
    {
        $version1FilePath = 'swagger-1-0-0.json';
        $version2FilePath = 'swagger-2-0-0.json';
        $versions = [
            [
                'appVersion' => '1.0.0',
                'host' => env('APP_URL'),
                'basePath' => '/',
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
                'file_path' => $version1FilePath,
            ],
            [
                'appVersion' => '2.0.0',
                'host' => env('APP_URL'),
                'basePath' => '/',
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
                'file_path' => $version2FilePath,
            ],
        ];

        config(['laravel-swagger.versions' => $versions]);

        $this->artisan('laravel-swagger:generate', ['--all-versions' => true]);

        foreach ([$version1FilePath, $version2FilePath] as $versionFilePath) {
            $this->assertTrue(file_exists(public_path($versionFilePath)));

            unlink(public_path($versionFilePath));
        }
    }

    public function testGenerateSwaggerDocToDefaultVersion()
    {
        $this->artisan('laravel-swagger:generate')->assertExitCode(0);

        $this->assertTrue(
            file_exists(public_path($this->defaultVersionConfig['file_path']))
        );

        unlink(public_path($this->defaultVersionConfig['file_path']));
    }

    public function testGenerateSwaggerDocPassingVersion()
    {
        $version2FilePath = 'swagger-2-0-0.json';
        $versions = [
            [
                'appVersion' => '1.0.0',
                'host' => env('APP_URL'),
                'basePath' => '/',
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
                'file_path' => 'swagger-1-0-0.json',
            ],
            [
                'appVersion' => '2.0.0',
                'host' => env('APP_URL'),
                'basePath' => '/',
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
                'file_path' => $version2FilePath,
            ]
        ];

        config(['laravel-swagger.versions' => $versions]);

        $this->artisan('laravel-swagger:generate', [
            '--api-version' => '2.0.0'
        ]);

        $this->assertTrue(file_exists(public_path($version2FilePath)));

        unlink(public_path($version2FilePath));
    }

    public function testGenerateSwaggerDocsFilteringByBasePath()
    {
        $version1FilePath = 'swagger-1-0-0.json';
        $version2FilePath = 'swagger-2-0-0.json';
        $version1BasePath = '/v1';
        $version2BasePath = '/v2';
        $versions = [
            [
                'appVersion' => '1.0.0',
                'host' => env('APP_URL'),
                'basePath' => $version1BasePath,
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
                'file_path' => $version1FilePath,
            ],
            [
                'appVersion' => '2.0.0',
                'host' => env('APP_URL'),
                'basePath' => $version2BasePath,
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
                'file_path' => $version2FilePath,
            ],
        ];

        config(['laravel-swagger.versions' => $versions]);

        $this->artisan('laravel-swagger:generate', ['--all-versions' => true]);

        $swaggerDocsVersion1 = json_decode(
            file_get_contents(public_path($version1FilePath)),
            true
        );

        foreach ($swaggerDocsVersion1['paths'] as $path => $config) {
            $this->assertStringStartsWith($version1BasePath, $path);
        }

        $swaggerDocsVersion2 = json_decode(
            file_get_contents(public_path($version2FilePath)),
            true
        );

        foreach ($swaggerDocsVersion2['paths'] as $path => $config) {
            $this->assertStringStartsWith($version2BasePath, $path);
        }
    }
}
