<?php

use Illuminate\Support\Facades\Route;

Route::get(config('laravel-swagger.routes.docs.path'), [
    'as' => config('laravel-swagger.routes.docs.name'),
    'middleware' => config('laravel-swagger.routes.docs.middleware', []),
    'uses' => '\Mtrajano\LaravelSwagger\Http\Controllers\SwaggerDocsController@index',
]);

Route::get(config('laravel-swagger.routes.docs.path').'/asset/{asset}', [
    'as' => 'laravel-swagger.asset',
    'middleware' => config('laravel-swagger.routes.docs.middleware', []),
    'uses' => '\Mtrajano\LaravelSwagger\Http\Controllers\SwaggerAssetController@index',
]);