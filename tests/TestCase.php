<?php

namespace Mtrajano\LaravelSwagger\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return ['Mtrajano\LaravelSwagger\SwaggerServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['router']->get('/users', 'Mtrajano\\LaravelSwagger\\Tests\\Controllers\\UserController@index');
        $app['router']->get('/users/{id}', 'Mtrajano\\LaravelSwagger\\Tests\\Controllers\\UserController@show');
    }
}