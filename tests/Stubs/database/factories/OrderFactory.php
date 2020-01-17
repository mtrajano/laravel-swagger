<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;
use Mtrajano\LaravelSwagger\Tests\Stubs\Models\Customer;
use Mtrajano\LaravelSwagger\Tests\Stubs\Models\Order;
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

$factory->define(Order::class, function (Faker $faker) {
    return [
        'value' => $faker->randomFloat(),
        'product_id' => function() {
            return factory(Product::class)->create()->getKey();
        },
        'customer_id' => function() {
            return factory(Customer::class)->create()->getKey();
        },
    ];
});
