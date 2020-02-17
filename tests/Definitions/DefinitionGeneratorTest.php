<?php

namespace Mtrajano\LaravelSwagger\Tests\Definitions;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\DefinitionGenerator;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\DefaultDefinitionHandler;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\DefaultErrorDefinitionHandler;
use Mtrajano\LaravelSwagger\LaravelSwaggerException;
use Mtrajano\LaravelSwagger\SwaggerDocsManager;
use Mtrajano\LaravelSwagger\Tests\TestCase;

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

        $app['router']
            ->get('customers/invalid_appends', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\CustomerController@invalidAppends')
            ->name('customers.invalid_appends');
    }

    public function testGenerateDefinitionToModelWithInvalidGetAppendsMethod()
    {
        $this->expectException(LaravelSwaggerException::class);

        $route = $this->newRouteByName('customers.invalid_appends');

        $this->generateDefinitionsForRoute($route);
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

    public function testGenerateDefinitionWhenClassOnMethodDocsIsNotModel()
    {
        $this->expectException(LaravelSwaggerException::class);

        $route = $this->newRouteByName('products.index');

        $this->generateDefinitionsForRoute($route);
    }

    public function testGenerateDefinitionWhenExistsMethodDocs()
    {
        $this->markTestSkipped('Fix flaky test');

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

    public function testGenerateDefinitionWhenExistsControllerDocs()
    {
        $this->markTestSkipped('Fix flaky test');

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

    public function testGenerateEmptyDefinitionWhenNotExistsDocs()
    {
        $route = $this->newRouteByName('orders.store');

        $this->generateDefinitionsForRoute($route)
            ->assertEmptyDefinitions();
    }

    public function testGenerateDefinitionWithRelations()
    {
        $this->markTestSkipped('Fix flaky test');

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
                    'StoreCustomerRequest' => [
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
                    'UpdateCustomerRequest' => [
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
                'http_code' => 422,
                'exception' => ValidationException::class,
                'handler' => get_class($validationDefinitionHandler)
            ],
            '403' => [
                'http_code' => 403,
                'exception' => AuthorizationException::class,
                'handler' => DefaultErrorDefinitionHandler::class
            ],
            '404' => [
                'http_code' => 404,
                'exception' => ModelNotFoundException::class,
                'handler' => DefaultErrorDefinitionHandler::class
            ],
            '401' => [
                'http_code' => 401,
                'exception' => AuthenticationException::class,
                'handler' => DefaultErrorDefinitionHandler::class
            ],
        ];

        config(['laravel-swagger.versions.0.errors_definitions' => $newErrorDefinitions]);

        $this->generateErrorDefinitionsForRoute($route);

        $this->assertHasDefinitions([
            'UpdateCustomerRequest' => [
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

    public function testGetAllRelations(): void
    {
        $route = $this->createMock(Route::class);

        $generator = new DefinitionGenerator($route);

        $relations = $generator->getAllRelations(new Customer());
        $relations = array_column($relations, 'related_model');

        $relation_names = array_map('class_basename', $relations);

        $this->assertEquals([
            'Order',
            'Company',
            'Address'
        ], $relation_names);
    }

    public function testOnlyRelationMethodsInvoked(): void
    {
        $mockCustomer = $this->createMock(Customer::class);
        $route = $this->createMock(Route::class);

        $generator = new DefinitionGenerator($route);

        $mockCustomer->expects($this->never())
            ->method('someOtherMethod');
        $mockCustomer->expects($this->once())
            ->method('orders');
        $mockCustomer->expects($this->once())
            ->method('company');
        $mockCustomer->expects($this->once())
            ->method('address');

        $generator->getAllRelations($mockCustomer);
    }

    public function testRelationshipsNotParsedOnFalse(): void
    {
        $route = $this->createMock(Route::class);
        $mockCustomer = $this->createMock(Customer::class);

        $route->method('getModel')
            ->willReturn($mockCustomer);

        $route->method('validMethods')
            ->willReturn(['get']);

        $generator = $this->getMockBuilder(DefinitionGenerator::class)
            ->setConstructorArgs([$route, [], false, false])
            ->setMethods(['getAllRelations'])
            ->getMock();

        $generator->expects($this->never())
            ->method('getAllRelations');

        $generator->generate();


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
        $lastVersionConfig = (new SwaggerDocsManager(config('laravel-swagger')))
            ->getLastVersionConfig();

        $this->definitions = (new DefinitionGenerator(
            $route,
            $lastVersionConfig['errors_definitions'],
            true,
            true
        ))->generate();

        return $this;
    }

    private function generateErrorDefinitionsForRoute(Route $route)
    {
        $this->generateDefinitionsForRoute($route);

        $lastVersionConfig = (new SwaggerDocsManager(config('laravel-swagger')))
            ->getLastVersionConfig();

        $errorDefinitionsNames = array_keys($lastVersionConfig['errors_definitions']);

        $definitions = [];
        foreach ($this->definitions as $definition => $value) {
            if (in_array($definition, $errorDefinitionsNames) ||
                Str::endsWith($definition, 'Request')
            ) {
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

    private function assertPropertyTimestampsDefinitions(
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

    private function assertPropertyDefinitions(array $data)
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
                $this->assertNotEmpty(
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

class Customer extends Model
{
    protected function someProtectedMethod()
    {
        return [];
    }

    public function someOtherMethod()
    {
        return 'blah';
    }

    public function orders(): HasMany
    {
        return $this->hasMany('Mtrajano\LaravelSwagger\Tests\Definitions\Order');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo('Mtrajano\LaravelSwagger\Tests\Definitions\Company');
    }

    public function address(): HasOne
    {
        return $this->hasOne('Mtrajano\LaravelSwagger\Tests\Definitions\Address');
    }
}

class Order extends Model
{
    public function customer()
    {
        return $this->belongsTo('Mtrajano\LaravelSwagger\Tests\Definitions\Customer');
    }
}

class Company extends Model
{
    public function customers()
    {
        return $this->hasMany('Mtrajano\LaravelSwagger\Tests\Definitions\Customer');
    }
}

class Address extends Model
{
    public function customer()
    {
        return $this->belongsTo('Mtrajano\LaravelSwagger\Tests\Definitions\Customer');
    }
}
