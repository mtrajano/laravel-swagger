<?php

namespace Mtrajano\LaravelSwagger\Tests\DataObjects;

use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Database\Eloquent\Model as LaravelModel;
use Mtrajano\LaravelSwagger\DataObjects;
use Mtrajano\LaravelSwagger\LaravelSwaggerException;
use Mtrajano\LaravelSwagger\Tests\TestCase;

class RouteTest extends TestCase
{
    private $_route;
    private $_laravel_route;
    private $_laravel_middleware = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->_laravel_route = $this->createMock(LaravelRoute::class);
        $this->_laravel_route
            ->method('gatherMiddleware')
            ->willReturnCallback(function() {
                return $this->_laravel_middleware;
            });

        $this->_route = new DataObjects\Route($this->_laravel_route);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['router']
            ->get('/only-route-middleware', 'Mtrajano\LaravelSwagger\Tests\DataObjects\OnlyRouteMiddleware@index')
            ->name('only-route-middleware')
            ->middleware('route-middleware:test,test2');
        $app['router']
            ->get('/controller-middleware', 'Mtrajano\LaravelSwagger\Tests\DataObjects\ControllerMiddleware@index')
            ->name('controller-middleware');
        $app['router']
            ->get('/middleware-controller-route', 'Mtrajano\LaravelSwagger\Tests\DataObjects\MiddlewareControllerRoute@index')
            ->name('middleware-controller-route')
            ->middleware('test');
        $app['router']
            ->get('/exception-route', 'Mtrajano\LaravelSwagger\Tests\DataObjects\ExceptionRoute@index')
            ->name('exception-route');
        $app['router']
            ->get('/model-route-action', 'Mtrajano\LaravelSwagger\Tests\DataObjects\ModelRoute@index')
            ->name('model-route-action');
        $app['router']
            ->get('/model-route-invalid', 'Mtrajano\LaravelSwagger\Tests\DataObjects\ModelRoute@show')
            ->name('model-route-invalid');
        $app['router']
            ->get('/model-route-class', 'Mtrajano\LaravelSwagger\Tests\DataObjects\ModelRoute@delete')
            ->name('model-route-class');

    }

    public function testGetOriginalUriDoesNotPrefixIfPrefixedAlready(): void
    {
        $this->_laravel_route
            ->method('uri')
            ->willReturn('/path/{user_id?}');

        $this->assertEquals('/path/{user_id?}', $this->_route->getOriginalUri());
    }

    public function testGetOriginaluriPrefixesIfNotAlready(): void
    {
        $this->_laravel_route
            ->method('uri')
            ->willReturn('path/{user_id?}');

        $this->assertEquals('/path/{user_id?}', $this->_route->getOriginalUri());
    }

    public function testGetUriStripsOptionalTag(): void
    {
        $this->_laravel_route
            ->method('uri')
            ->willReturn('/path/{user_id?}');

        $this->assertEquals('/path/{user_id}', $this->_route->getUri());
    }

    public function testGetMiddleware(): void
    {
        $this->_laravel_middleware = [
            'signed',
            'auth:api',
            'throttle:60,1'
        ];

        $this->_route = new DataObjects\Route($this->_laravel_route);
        $middleware = $this->_route->getMiddleware();

        $this->assertCount(3, $middleware);
        $this->assertContainsOnlyInstancesOf(DataObjects\Middleware::class, $middleware);
    }

    /**
     * @todo test with actual laravel route
     */
    public function testGetAction():  void
    {
        $this->_laravel_route
            ->method('getActionName')
            ->willReturn('SomeClass@action');

        $this->assertEquals('SomeClass@action', $this->_route->getAction());
    }

    public function testGetMethods(): void
    {
        $this->_laravel_route
            ->method('methods')
            ->willReturn(['GET', 'POST']);

        $this->assertEquals(['get', 'post'], $this->_route->getMethods());
    }

    public function testActionMethodsDoesNotReturnHead(): void
    {
        $this->_laravel_route
            ->method('methods')
            ->willReturn(['GET', 'POST', 'HEAD']);

        $this->assertEquals(['get', 'post'], $this->_route->getActionMethods());
    }

    /**
     * @todo test with actual laravel route (including route without an alias)
     */
    public function testGetName(): void
    {
        $this->_laravel_route
            ->method('getName')
            ->willReturn('photos.index');

        $this->assertEquals('photos.index', $this->_route->getName());
    }

    // -------------------
    // Using actual routes
    // -------------------

    public function testGetThrows(): void
    {
        $route = $this->_getRouteByName('exception-route');

        $this->assertEquals(['Exception', 'SpaceExcetion', 'TabException'], $route->getThrows());
    }

    public function testGetThrowsWithoutThrowsTag(): void
    {
        $route = $this->_getRouteByName('only-route-middleware');

        $this->assertEmpty($route->getThrows());
    }

    public function testGetModelFromActionDocs(): void
    {
        $route = $this->_getRouteByName('model-route-action');

        $this->assertInstanceOf(ActionModel::class, $route->getModel());
    }

    public function testGetModelFromClassDocs(): void
    {
        $route = $this->_getRouteByName('model-route-class');

        $this->assertInstanceOf(ClassModel::class, $route->getModel());
    }

    public function testGetModelThatIsInvalid(): void
    {
        $this->expectException(LaravelSwaggerException::class);

        $route = $this->_getRouteByName('model-route-invalid');

        $route->getModel();
    }

    public function testGetModelWithoutModelTag(): void
    {
        $route = $this->_getRouteByName('only-route-middleware');

        $this->assertNull($route->getModel());
    }

    public function provideRoutesWithMiddleware(): array
    {
        return [
            [
                'controller-middleware',
                [
                    [
                        'name' => 'auth',
                        'params' => [],
                    ],
                ],
            ],
            [
                'middleware-controller-route',
                [
                    [
                        'name' => 'test',
                        'params' => [],
                    ],
                    [
                        'name' => 'auth',
                        'params' => [
                            'api',
                        ],
                    ],
                ],
            ],
            [
                'only-route-middleware',
                [
                    [
                        'name' => 'route-middleware',
                        'params' => [
                            'test',
                            'test2',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideRoutesWithMiddleware
     */
    public function testCreateFromOnlyControllerWithMiddleware(string $routeName, array $expectedMiddleware): void
    {
        $route = $this->_getRouteByName($routeName);

        $this->assertIsArray($route->getMiddleware());
        $this->assertNotEmpty($route->getMiddleware());
        $this->assertContainsOnlyInstancesOf(DataObjects\Middleware::class, $route->getMiddleware());

        foreach ($route->getMiddleware() as $key => $middleware) {
            $this->assertEquals($expectedMiddleware[$key]['name'], $middleware->name());
            $this->assertEquals($expectedMiddleware[$key]['params'], $middleware->parameters());
        }
    }

    private function _getRouteByName(string $routeName): DataObjects\Route
    {
        $laravelRoute = app('router')->getRoutes()->getByName($routeName);

        return new DataObjects\Route($laravelRoute);
    }
}

/**
 * @todo move these to the stubs namespace
 */
class ControllerMiddleware extends LaravelController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
    }
}

class MiddlewareControllerRoute extends LaravelController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
    }
}

class OnlyRouteMiddleware extends LaravelController
{
    public function index()
    {
    }
}

class ExceptionRoute extends LaravelController
{
    /**
     * some description.
     *
     * @throws \Exception
     * @throws    \SpaceExcetion
     * @throws  \TabException
     */
    public function index()
    {
    }
}

/**
 * @model \Mtrajano\LaravelSwagger\Tests\DataObjects\ClassModel
 */
class ModelRoute extends LaravelController
{
    /**
     * @model \Mtrajano\LaravelSwagger\Tests\DataObjects\ActionModel
     */
    public function index()
    {
    }

    /**
     * @model \Mtrajano\LaravelSwagger\Tests\DataObjects\InvalidModel
     */
    public function show()
    {
    }

    public function delete()
    {
    }
}

class ClassModel extends LaravelModel
{
}

class ActionModel extends LaravelModel
{
}

class InvalidModel
{
}