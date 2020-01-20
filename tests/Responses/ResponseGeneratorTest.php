<?php

namespace Mtrajano\LaravelSwagger\Tests\Responses;

use Illuminate\Routing\Router;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Responses\ResponseGenerator;
use Mtrajano\LaravelSwagger\Tests\TestCase;

class ResponseGeneratorTest extends TestCase
{
    /**
     * @var array
     */
    private $responses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../Stubs/database/migrations');

        $this->loadLaravelMigrations(['--database' => 'laravel-swagger']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/../Stubs/database/factories');
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
                    ],
                ],
            ],
            [
                'customers.store',
                [
                    '201' => [
                        'description' => 'Created',
                        'schema' => [
                            '$ref' => '#/definitions/Customer'
                        ],
                    ],
                    '422' => [
                        'description' => 'Validation errors',
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
                    ],
                    '404' => [
                        'description' => 'Model not found',
                    ],
                    '403' => [
                        'description' => 'Forbidden',
                    ],
                    '401' => [
                        'description' => 'Unauthenticated',
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
                    ],
                ],
            ],
            [
                'customers.show',
                [
                    '200' => [
                        'description' => 'OK',
                        'schema' => [
                            '$ref' => '#/definitions/Customer'
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
                    ]
                ]
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

    private function getLaravelRouter(): Router
    {
        return app('router');
    }

    private function generateResponsesFromRoute(Route $route)
    {
        $this->responses = (new ResponseGenerator($route))->generate();
    }
}