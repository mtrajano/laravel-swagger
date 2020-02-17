<?php

use Illuminate\Support\Facades\Route;
use Mtrajano\LaravelSwagger\Http\Controllers\SwaggerAssetController;
use Mtrajano\LaravelSwagger\Http\Controllers\SwaggerDocsController;

Route::get(config('laravel-swagger.route.path'), [
    'as' => config('laravel-swagger.route.name'),
    'middleware' => config('laravel-swagger.route.middleware', []),
    'uses' => SwaggerDocsController::class,
]);

Route::get(config('laravel-swagger.routes.docs.path').'/asset/{asset}', [
    'as' => 'laravel-swagger.asset',
    'middleware' => config('laravel-swagger.routes.docs.middleware', []),
    'uses' => SwaggerAssetController::class,
]);