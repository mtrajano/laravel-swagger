<?php

namespace Mtrajano\LaravelSwagger\Tests\DataObjects;

use Illuminate\Routing\Controller;
use Mtrajano\LaravelSwagger\DataObjects\Middleware;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Tests\TestCase;

class RouteTest extends TestCase
{
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
    }

    public function provideRoutesWithMiddleware() : array
    {
        return [
            [
                'controller-middleware',
                [
                    [
                        'name' => 'auth',
                        'params' => []
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
                            'api'
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

    public function testGetThrows()
    {
        $laravelRoute = app('router')->getRoutes()->getByName('exception-route');

        $route = new Route($laravelRoute);

        $this->assertEquals(['Exception', 'SpaceExcetion', 'TabException'], $route->getThrows());
    }

    /**
     * @dataProvider provideRoutesWithMiddleware
     */
    public function testCreateFromOnlyControllerWithMiddleware(string $routeName, array $expectedMiddleware)
    {
        $laravelRoute = app('router')->getRoutes()->getByName($routeName);

        $route = new Route($laravelRoute);

        $this->assertIsArray($route->middleware());
        $this->assertNotEmpty($route->middleware());
        $this->assertContainsOnlyInstancesOf(Middleware::class, $route->middleware());

        foreach ($route->middleware() as $key => $middleware) {
            $this->assertEquals($expectedMiddleware[$key]['name'], $middleware->name());
            $this->assertEquals($expectedMiddleware[$key]['params'], $middleware->parameters());
        }
    }
}

class ControllerMiddleware extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index() {}
}

class MiddlewareControllerRoute extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index() {}
}

class OnlyRouteMiddleware extends Controller
{
    public function index() {}
}

class ExceptionRoute extends Controller
{
    /**
     * some description
     *
     * @throws \Exception
     * @throws    \SpaceExcetion
     * @throws  \TabException
     */
    public function index() {}
}