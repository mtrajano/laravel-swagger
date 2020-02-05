<?php

namespace Mtrajano\LaravelSwagger\Tests\Console;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\DefaultErrorDefinitionHandler;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\ValidationErrorDefinitionHandler;
use Mtrajano\LaravelSwagger\SwaggerDocsManager;
use Mtrajano\LaravelSwagger\Tests\TestCase;

class GenerateSwaggerDocCommandTest extends TestCase
{
    private $lastVersionConfig;

    /**
     * @var SwaggerDocsManager
     */
    private $swaggerDocsManager;

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

        $this->swaggerDocsManager = new SwaggerDocsManager(config('laravel-swagger'));
        $this->lastVersionConfig = $this->swaggerDocsManager->getLastVersionConfig();
    }

    public function testGenerateSwaggerDocToAllVersion()
    {
        $version1FilePath = 'swagger-1.0.0.json';
        $version2FilePath = 'swagger-2.0.0.json';
        $versions = [
            $this->defaultConfig([
                'appVersion' => '1.0.0',
            ]),
            $this->defaultConfig([
                'appVersion' => '2.0.0',
            ]),
        ];

        config(['laravel-swagger.versions' => $versions]);

        $this->artisan('laravel-swagger:generate', ['--api-version' => '*']);

        foreach ([$version1FilePath, $version2FilePath] as $versionFilePath) {
            $this->assertTrue(file_exists(public_path($versionFilePath)));

            unlink(public_path($versionFilePath));
        }
    }

    public function testGenerateSwaggerDocsToAllVersionsByDefault()
    {
        $version1FilePath = 'swagger-1.0.0.json';
        $version2FilePath = 'swagger-2.0.0.json';
        $versions = [
            $this->defaultConfig([
                'appVersion' => '1.0.0',
            ]),
            $this->defaultConfig([
                'appVersion' => '2.0.0',
            ]),
        ];

        config(['laravel-swagger.versions' => $versions]);

        $this->artisan('laravel-swagger:generate');

        foreach ([$version1FilePath, $version2FilePath] as $versionFilePath) {
            $this->assertTrue(file_exists(public_path($versionFilePath)));

            unlink(public_path($versionFilePath));
        }
    }

    public function testGenerateSwaggerDocPassingVersion()
    {
        $version2FilePath = 'swagger-2.0.0.json';
        $versions = [
            $this->defaultConfig([
                'appVersion' => '1.0.0',
            ]),
            $this->defaultConfig([
                'appVersion' => '2.0.0',
            ]),
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
        $version1FilePath = 'swagger-1.0.0.json';
        $version2FilePath = 'swagger-2.0.0.json';
        $version1BasePath = '/v1';
        $version2BasePath = '/v2';
        $versions = [
            $this->defaultConfig([
                'appVersion' => '1.0.0',
                'basePath' => $version1BasePath,
                'file_path' => $version2FilePath,
            ]),
            $this->defaultConfig([
                'appVersion' => '2.0.0',
                'basePath' => $version2BasePath,
                'file_path' => $version2FilePath,
            ]),
        ];

        config(['laravel-swagger.versions' => $versions]);

        $this->artisan('laravel-swagger:generate');

        $swaggerDocsVersion1 = json_decode(
            file_get_contents(public_path($version1FilePath)),
            true
        );

        $this->assertEquals($version1BasePath, $swaggerDocsVersion1['basePath']);

        $swaggerDocsVersion2 = json_decode(
            file_get_contents(public_path($version2FilePath)),
            true
        );

        $this->assertEquals($version2BasePath, $swaggerDocsVersion2['basePath']);
    }

    private function defaultConfig(array $config = [])
    {
        return array_merge([
            'security_definition_type' => 'oauth2',
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
            'file_path' => 'swagger-1.0.0.json',
            'errors_definitions' => [
                'UnprocessableEntity' => [
                    'http_code' => 422,
                    'exception' => ValidationException::class,
                    'handler' => ValidationErrorDefinitionHandler::class
                ],
                'Forbidden' => [
                    'http_code' => 403,
                    'exception' => AuthorizationException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
                'NotFound' => [
                    'http_code' => 404,
                    'exception' => ModelNotFoundException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
                'Unauthenticated' => [
                    'http_code' => 401,
                    'exception' => AuthenticationException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
            ],
        ], $config);
    }
}
