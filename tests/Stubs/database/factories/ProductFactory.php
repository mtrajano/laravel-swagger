<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;
use Faker\Generator as Faker;
use Mtrajano\LaravelSwagger\Tests\Stubs\Models\Product;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Product::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'price' => $faker->randomFloat(),
        'active' => $faker->boolean,
        'finished_at' => Carbon::now(),
    ];
});
