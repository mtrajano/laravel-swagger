<?php

namespace Mtrajano\LaravelSwagger\Tests\Definitions;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Router;
use Illuminate\Validation\ValidationException;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\DefinitionGenerator;
use Mtrajano\LaravelSwagger\Definitions\Handlers\DefaultDefinitionHandler;
use Mtrajano\LaravelSwagger\Definitions\Handlers\DefaultErrorDefinitionHandler;
use Mtrajano\LaravelSwagger\SwaggerDocsManager;
use Mtrajano\LaravelSwagger\Tests\TestCase;
use RuntimeException;

class DefinitionGeneratorTest extends TestCase
{
    /**
     * @var array
     */
    private $definitions;

    /**
     * @var string
     */
    private $definition;

    /**
     * @var SwaggerDocsManager
     */
    private $swaggerDocsManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../Stubs/database/migrations');

        $this->loadLaravelMigrations(['--database' => 'laravel-swagger']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/../Stubs/database/factories');

        $this->swaggerDocsManager = new SwaggerDocsManager(config('laravel-swagger'));
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['router']
            ->post('/orders', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\OrderController@store')
            ->name('orders.store');
        $app['router']
            ->post('/orders/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\OrderController@show')
            ->name('orders.show');

        $app['router']
            ->get('products', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\ProductController@index')
            ->name('products.index');
        $app['router']
            ->post('products', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\ProductController@store')
            ->name('products.store');
        $app['router']
            ->get('products/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\ProductController@show')
            ->name('products.show');

        $app['router']
            ->get('customers', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@index')
            ->name('customers.index');
        $app['router']
            ->post('customers', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@store')
            ->name('customers.store');
        $app['router']
            ->put('customers/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@update')
            ->name('customers.update');
        $app['router']
            ->delete('customers/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@destroy')
            ->name('customers.destroy');
    }

    public function provideNotAllowedHttpMethods()
    {
        return [
            [['get', 'delete']],
            [['get', 'post', 'delete']],
            [['get', 'put']],
            [['post', 'delete']],
            [['patch', 'get']],
        ];
    }

    /**
     * @dataProvider provideNotAllowedHttpMethods
     * @param array $notAllowwedHttpMethods
     */
    public function testReturnEmptyDefinitionToNotAllowedHttpMethod(
        array $notAllowwedHttpMethods
    ) {
        $routeMock = $this->createMock(Route::class);
        $routeMock->method('methods')->willReturn($notAllowwedHttpMethods);

        $this->generateDefinitionsForRoute($routeMock)
            ->assertEmptyDefinitions();
    }

    public function testReturnErrorTryingGenerateWhenClassOnMethodDocsIsNotModel()
    {
        $this->expectException(RuntimeException::class);

        $route = $this->newRouteByName('products.index');

        $this->generateDefinitionsForRoute($route);
    }

    public function testReturnDefinitionWhenExistsMethodDocs()
    {
        $route = $this->newRouteByName('products.show');

        $this->generateDefinitionsForRoute($route);

        $this->assertHasDefinition('Product', function (self $test) {
            $test
                ->assertPropertyDefinitions([
                    'property' => 'id',
                    'type' => 'integer'
                ])
                ->assertPropertyDefinitions([
                    'property' => 'name',
                    'type' => 'string'
                ])
                ->assertPropertyDefinitions([
                    'property' => 'price',
                    'type' => 'number',
                    'format' => 'float',
                ])
                ->assertPropertyTimestampsDefinitions(['finished_at']);
        });
    }

    public function testReturnDefinitionWhenExistsControllerDocs()
    {
        $route = $this->newRouteByName('products.store');

        $this->generateDefinitionsForRoute($route);

        $this->assertHasDefinition('Product', function (self $test) {
            $test
                ->assertPropertyDefinitions([
                    'property' => 'id',
                    'type' => 'integer'
                ])
                ->assertPropertyDefinitions([
                    'property' => 'name',
                    'type' => 'string'
                ])
                ->assertPropertyDefinitions([
                    'property' => 'price',
                    'type' => 'number',
                    'format' => 'float',
                ])
                ->assertPropertyTimestampsDefinitions(['finished_at']);
        });
    }

    public function testReturnEmptyDefinitionWhenNotExistsDocs()
    {
        $route = $this->newRouteByName('orders.store');

        $this->generateDefinitionsForRoute($route)
            ->assertEmptyDefinitions();
    }

    public function testReturnDefinitionWithRelations()
    {
        $route = $this->newRouteByName('orders.show');

        $this->generateDefinitionsForRoute($route)
            ->assertHasDefinition('ProductItem')
            ->assertHasDefinition('Product', function (self $test) {
                $test
                    ->assertPropertyDefinitions([
                        'property' => 'id',
                        'type' => 'integer'
                    ])
                    ->assertPropertyDefinitions([
                        'property' => 'name',
                        'type' => 'string'
                    ])
                    ->assertPropertyDefinitions([
                        'property' => 'price',
                        'type' => 'number',
                        'format' => 'float',
                    ])
                    ->assertPropertyTimestampsDefinitions(['finished_at'])
                    ->assertRefProperty([
                        'property' => 'items',
                        'value' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/definitions/ProductItem'
                            ]
                        ],
                    ]);
            })
            ->assertHasDefinition('Order', function (self $test) {
                $test
                    ->assertPropertyDefinitions([
                        'property' => 'id',
                        'type' => 'integer'
                    ])
                    ->assertPropertyDefinitions([
                        'property' => 'value',
                        'type' => 'number',
                        'format' => 'float',
                    ])
                    ->assertPropertyDefinitions([
                        'property' => 'formatted_value',
                        'type' => 'string',
                    ])
                    ->assertPropertyTimestampsDefinitions()
                    ->assertRefProperty([
                        'property' => 'product',
                        'value' => ['$ref' => '#/definitions/Product'],
                    ])
                    ->assertRefProperty([
                        'property' => 'customer',
                        'value' => ['$ref' => '#/definitions/Customer'],
                    ]);
            })
            ->assertHasDefinition('Customer', function (self $test) {
                $test
                    ->assertPropertyDefinitions([
                        'property' => 'id',
                        'type' => 'integer',
                    ])->assertPropertyDefinitions([
                        'property' => 'name',
                        'type' => 'string',
                    ])
                    ->assertPropertyDefinitions([
                        'property' => 'email',
                        'type' => 'string',
                    ])
                    ->assertPropertyTimestampsDefinitions();
            });
    }

    public function provideRouteToReturnErrorDefinition()
    {
        return [
            [
                'customers.index',
                [],
            ],
            [
                'customers.store',
                [
                    'UnprocessableEntity' => [
                        [
                            'property' => 'message',
                            'type' => 'string',
                        ],
                        [
                            'property' => 'errors',
                            'value' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => [
                                        'type' => 'array',
                                        'description' => 'Errors on "name" parameter',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'email' => [
                                        'type' => 'array',
                                        'description' => 'Errors on "email" parameter',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ]
                    ],
                ],
            ],
            [
                'customers.update',
                [
                    'UnprocessableEntity' => [
                        [
                            'property' => 'message',
                            'type' => 'string',
                        ],
                        [
                            'property' => 'errors',
                            'value' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => [
                                        'type' => 'array',
                                        'description' => 'Errors on "name" parameter',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'email' => [
                                        'type' => 'array',
                                        'description' => 'Errors on "email" parameter',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ]
                    ],
                    'NotFound' => [
                        [
                            'property' => 'message',
                            'type' => 'string',
                        ],
                    ],
                    'Unauthenticated' => [
                        [
                            'property' => 'message',
                            'type' => 'string',
                        ],
                    ],
                    'Forbidden' => [
                        [
                            'property' => 'message',
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            [
                'customers.destroy',
                [
                    'NotFound' => [
                        [
                            'property' => 'message',
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideRouteToReturnErrorDefinition
     *
     * @param string $routeName
     * @param array $definitions
     */
    public function testReturnErrorDefinition(string $routeName, array $definitions)
    {
        $route = $this->newRouteByName($routeName);

        $this->generateErrorDefinitionsForRoute($route);

        $this->assertCount(count($this->definitions), $definitions);

        $this->assertHasDefinitions($definitions);
    }

    public function testReturnErrorDefinitionChangingConfig()
    {
        $route = $this->newRouteByName('customers.update');

        $validationDefinitionHandler = new class(
            $route,
            '422'
        ) extends DefaultDefinitionHandler {
            protected function getDefinitionContent(): array
            {
                return [
                    'type' => 'object',
                    'required' => [
                        'code',
                        'field',
                        'message',
                    ],
                    'properties' => [
                        'code' => [
                            'type' => 'string',
                            'example' => 'A123',
                        ],
                        'field' => [
                            'type' => 'string',
                            'example' => 'email',
                        ],
                        'message' => [
                            'type' => 'string',
                            'example' => 'Invalid email'
                        ],
                    ],
                ];
            }
        };

        $newErrorDefinitions = [
            '422' => [
                'exception' => ValidationException::class,
                'handler' => get_class($validationDefinitionHandler)
            ],
            '403' => [
                'exception' => AuthorizationException::class,
                'handler' => DefaultErrorDefinitionHandler::class
            ],
            '404' => [
                'exception' => ModelNotFoundException::class,
                'handler' => DefaultErrorDefinitionHandler::class
            ],
            '401' => [
                'exception' => AuthenticationException::class,
                'handler' => DefaultErrorDefinitionHandler::class
            ],
        ];

        config(['laravel-swagger.versions.0.errors_definitions' => $newErrorDefinitions]);

        $this->generateErrorDefinitionsForRoute($route);

        $this->assertHasDefinitions([
            '422' => [
                [
                    'property' => 'code',
                    'type' => 'string',
                ],
                [
                    'property' => 'field',
                    'type' => 'string'
                ],
                [
                    'property' => 'message',
                    'type' => 'string',
                ],
            ],
            '404' => [
                [
                    'property' => 'message',
                    'type' => 'string',
                ],
            ],
            '401' => [
                [
                    'property' => 'message',
                    'type' => 'string',
                ],
            ],
            '403' => [
                [
                    'property' => 'message',
                    'type' => 'string',
                ],
            ],
        ]);
    }

    private function getLaravelRouter(): Router
    {
        return app('router');
    }

    private function assertRefProperty(array $data)
    {
        $definition = $data['definition'] ?? $this->definition;

        $this->assertArrayHasKey(
            $data['property'],
            $this->definitions[$definition]['properties']
        );
        $this->assertEquals(
            $data['value'],
            $this->definitions[$definition]['properties'][$data['property']]
        );

        return $this;
    }

    private function generateDefinitionsForRoute(Route $route)
    {
        $defaultConfig = (new SwaggerDocsManager(config('laravel-swagger')))
            ->getDefaultVersionConfig();

        $this->definitions = (new DefinitionGenerator(
            $route,
            $defaultConfig['errors_definitions'])
        )->generate();
        return $this;
    }

    private function generateErrorDefinitionsForRoute(Route $route)
    {
        $this->generateDefinitionsForRoute($route);

        $defaultConfig = (new SwaggerDocsManager(config('laravel-swagger')))
            ->getDefaultVersionConfig();

        $errorDefinitionsNames = array_keys($defaultConfig['errors_definitions']);

        $definitions = [];
        foreach ($this->definitions as $definition => $value) {
            if (in_array($definition, $errorDefinitionsNames)) {
                $definitions[$definition] = $value;
            }
        }
        $this->definitions = $definitions;

        return $this;
    }

    private function assertHasDefinition(
        string $definition,
        Closure $assertDefintionsContent = null
    ) {
        $this->assertArrayHasKey($definition, $this->definitions);
        $this->assertArrayHasKey('type', $this->definitions[$definition]);
        $this->assertEquals('object', $this->definitions[$definition]['type']);
        $this->assertArrayHasKey('properties', $this->definitions[$definition]);

        if ($assertDefintionsContent) {
            $this->definition = $definition;
            $assertDefintionsContent($this);
        }

        return $this;
    }

    public function assertPropertyTimestampsDefinitions(
        array $timestamps = [],
        string $definition = null
    ) {
        $definition = $definition ?: $this->definition;

        return $this->assertPropertyDefinitions([
            'definition' => $definition,
            'property' => array_merge(['created_at', 'updated_at'], $timestamps),
            'type' => 'string',
            'format' => 'date-time',
        ]);
    }

    public function assertPropertyDefinitions(array $data)
    {
        $definition = $data['definition'] ?? $this->definition;
        $properties = (array) $data['property'];
        $type = $data['type'] ?? null;
        $example = $data['example'] ?? true;
        $format = $data['format'] ?? null;

        foreach ($properties as $property) {
            $value = $data['value'] ?? false;
            if ($value) {
                $this->assertEquals($value, $this->definitions[$definition]['properties'][$property]);
                continue;
            }

            $this->assertArrayHasKey(
                $property,
                $this->definitions[$definition]['properties'],
                "The definition \"{$definition}\" doesn't have the property \"{$property}\""
            );
            $this->assertArrayHasKey(
                'type',
                $this->definitions[$definition]['properties'][$property]
            );
            $this->assertEquals(
                $type,
                $this->definitions[$definition]['properties'][$property]['type']
            );

            if ($format) {
                $this->assertArrayHasKey(
                    'format',
                    $this->definitions[$definition]['properties'][$property]
                );
                $this->assertEquals(
                    $format,
                    $this->definitions[$definition]['properties'][$property]['format']
                );
            }

            if ($example) {
                $this->assertArrayHasKey(
                    'example',
                    $this->definitions[$definition]['properties'][$property]
                );
                $this->assertNotNull(
                    $this->definitions[$definition]['properties'][$property]['example']
                );
            }
        }

        return $this;
    }

    private function assertEmptyDefinitions()
    {
        $this->assertEmpty($this->definitions);
        return $this;
    }

    private function newRouteByName(string $routeName)
    {
        return new Route(
            $this->getLaravelRouter()->getRoutes()->getByName($routeName)
        );
    }

    private function assertHasDefinitions(array $definitions)
    {
        foreach ($definitions as $definition => $propertyDefinitions) {
            $this->assertHasDefinition($definition, function (self $test) use ($propertyDefinitions) {
                foreach ($propertyDefinitions as $propertyDefinition) {
                    $test->assertPropertyDefinitions($propertyDefinition);
                }
            });
        }
    }
}