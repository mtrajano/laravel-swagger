<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
    ];

    protected $casts = [
        'name' => 'string',
        'email' => 'string',
    ];
}