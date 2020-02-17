<?php

namespace Mtrajano\LaravelSwagger\Tests\Responses;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Router;
use Illuminate\Validation\ValidationException;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\DefaultErrorDefinitionHandler;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\ValidationErrorDefinitionHandler;
use Mtrajano\LaravelSwagger\Responses\ResponseGenerator;
use Mtrajano\LaravelSwagger\SwaggerDocsManager;
use Mtrajano\LaravelSwagger\Tests\TestCase;
use RuntimeException;

class ResponseGeneratorTest extends TestCase
{
    /**
     * @var array
     */
    private $responses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../Stubs/database/migrations');

        $this->loadLaravelMigrations(['--database' => 'laravel-swagger']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__ . '/../Stubs/database/factories');
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['router']
            ->get('/customers', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@index')
            ->name('customers.index')
            ->middleware('auth');
        $app['router']
            ->post('/customers', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@store')
            ->name('customers.store');
        $app['router']
            ->put('/customers/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@update')
            ->name('customers.update');
        $app['router']
            ->delete('/customers/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@destroy')
            ->name('customers.destroy');
        $app['router']
            ->get('/customers/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@show')
            ->name('customers.show');
        $app['router']
            ->get('/orders', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\OrderController@index')
            ->name('orders.index');
    }

    public function provideRoutesToGenerateResponse()
    {
        return [
            [
                'customers.index',
                [
                    '200' => [
                        'description' => 'OK',
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/definitions/Customer',
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Unauthenticated',
                        'schema' => [
                            '$ref' => '#/definitions/Unauthenticated',
                        ],
                    ],
                ],
            ],
            [
                'customers.store',
                [
                    '201' => [
                        'description' => 'Created',
                        'schema' => [
                            '$ref' => '#/definitions/Customer',
                        ],
                    ],
                    '422' => [
                        'description' => 'Validation errors',
                        'schema' => [
                            '$ref' => '#/definitions/StoreCustomerRequest',
                        ],
                    ],
                ],
            ],
            [
                'customers.update',
                [
                    '204' => [
                        'description' => 'No Content',
                    ],
                    '422' => [
                        'description' => 'Validation errors',
                        'schema' => [
                            '$ref' => '#/definitions/UpdateCustomerRequest',
                        ],
                    ],
                    '404' => [
                        'description' => 'Model not found',
                        'schema' => [
                            '$ref' => '#/definitions/NotFound',
                        ],
                    ],
                    '403' => [
                        'description' => 'Forbidden',
                        'schema' => [
                            '$ref' => '#/definitions/Forbidden',
                        ],
                    ],
                    '401' => [
                        'description' => 'Unauthenticated',
                        'schema' => [
                            '$ref' => '#/definitions/Unauthenticated',
                        ],
                    ],
                ],
            ],
            [
                'customers.destroy',
                [
                    '204' => [
                        'description' => 'No Content',
                    ],
                    '404' => [
                        'description' => 'Model not found',
                        'schema' => [
                            '$ref' => '#/definitions/NotFound',
                        ],
                    ],
                ],
            ],
            [
                'customers.show',
                [
                    '200' => [
                        'description' => 'OK',
                        'schema' => [
                            '$ref' => '#/definitions/Customer',
                        ],
                    ],
                ],
            ],
            [
                'orders.index',
                [
                    '200' => [
                        'description' => 'OK',
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/definitions/Order',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideRoutesToGenerateResponse
     * @param string $routeName
     * @param array $response
     */
    public function testGeneratedResponses(
        string $routeName,
        array $response
    ) {
        $route = new Route($this->getLaravelRouter()->getRoutes()->getByName($routeName));

        $this->generateResponsesFromRoute($route);

        $this->assertEquals($response, $this->responses);
    }

    public function testGeneratedResponsesChangingConfig()
    {
        $newErrorDefinitions = [
            '422' => [
                'http_code' => 422,
                'exception' => ValidationException::class,
                'handler' => ValidationErrorDefinitionHandler::class,
            ],
            '403' => [
                'http_code' => 403,
                'exception' => AuthorizationException::class,
                'handler' => DefaultErrorDefinitionHandler::class,
            ],
            '404' => [
                'http_code' => 404,
                'exception' => ModelNotFoundException::class,
                'handler' => DefaultErrorDefinitionHandler::class,
            ],
            '401' => [
                'http_code' => 401,
                'exception' => AuthenticationException::class,
                'handler' => DefaultErrorDefinitionHandler::class,
            ],
        ];

        config(['laravel-swagger.versions.0.errors_definitions' => $newErrorDefinitions]);

        $routeName = 'customers.update';
        $response = [
            '204' => [
                'description' => 'No Content',
            ],
            '422' => [
                'description' => 'Validation errors',
                'schema' => [
                    '$ref' => '#/definitions/UpdateCustomerRequest',
                ],
            ],
            '404' => [
                'description' => 'Model not found',
                'schema' => [
                    '$ref' => '#/definitions/404',
                ],
            ],
            '403' => [
                'description' => 'Forbidden',
                'schema' => [
                    '$ref' => '#/definitions/403',
                ],
            ],
            '401' => [
                'description' => 'Unauthenticated',
                'schema' => [
                    '$ref' => '#/definitions/401',
                ],
            ],
        ];

        $route = new Route($this->getLaravelRouter()->getRoutes()->getByName($routeName));

        $this->generateResponsesFromRoute($route);

        $this->assertEquals($response, $this->responses);
    }

    public function testGeneratedResponsesWhenHttpCodeHasNotHandler()
    {
        $this->expectException(RuntimeException::class);

        $newErrorDefinitions = [
            '404' => [
                'http_code' => 404,
                'exception' => ModelNotFoundException::class,
                'handler' => DefaultErrorDefinitionHandler::class,
            ],
            '401' => [
                'http_code' => 401,
                'exception' => AuthenticationException::class,
                'handler' => DefaultErrorDefinitionHandler::class,
            ],
        ];

        config(['laravel-swagger.versions.0.errors_definitions' => $newErrorDefinitions]);

        $routeName = 'customers.update';

        $route = new Route($this->getLaravelRouter()->getRoutes()->getByName($routeName));

        $this->generateResponsesFromRoute($route);
    }

    private function getLaravelRouter(): Router
    {
        return app('router');
    }

    private function generateResponsesFromRoute(Route $route)
    {
        $config = (
            new SwaggerDocsManager(config('laravel-swagger'))
        )->getLastVersionConfig();

        $this->responses = (
            new ResponseGenerator($route, $config['errors_definitions'])
        )->generate();
    }
}
