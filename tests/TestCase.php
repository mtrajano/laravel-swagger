<?php

namespace Mtrajano\LaravelSwagger\Tests;

use Laravel\Passport\Passport;
use Mtrajano\LaravelSwagger\Tests\Stubs\Middleware\RandomMiddleware;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            'Laravel\Passport\PassportServiceProvider',
            'Mtrajano\LaravelSwagger\SwaggerServiceProvider'
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:r+nhiNB44vkb+4+Yd3NiU1E+W/xbMQMTy6se5k3m5oE=');

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'laravel-swagger');
        $app['config']->set('database.connections.laravel-swagger', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['router']->middleware(['some-middleware', 'scope:user-read'])->group(function () use ($app) {
            $app['router']->get('/users', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\UserController@index');
            $app['router']->get('/users/{id}', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\UserController@show');
            $app['router']->post('/users', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\UserController@store')
                ->middleware('scopes:user-write,user-read');
            $app['router']->get('/users/details', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\UserController@details');
            $app['router']->get('/users/ping', function () {
                return 'pong';
            });
        });

        $app['router']->get('/api', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\ApiController@index')
            ->middleware(RandomMiddleware::class);
        $app['router']->put('/api/store', 'Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\ApiController@store');

        Passport::routes();

        $app['router']->aliasMiddleware('scopes', \Laravel\Passport\Http\Middleware\CheckScopes::class);
        $app['router']->aliasMiddleware('scope', \Laravel\Passport\Http\Middleware\CheckForAnyScope::class);

        Passport::tokensCan([
            'user-read' => 'Read user information such as email, name and phone number',
            'user-write' => 'Update user information',
        ]);
    }
}
