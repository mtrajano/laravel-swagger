<?php

namespace Mtrajano\LaravelSwagger\Tests\Controllers;

use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function index()
    {
        return json_encode([['first_name' => 'John'], ['first_name' => 'Jack']]);
    }

    public function show($id)
    {
        return json_encode(['first_name' => 'John']);
    }
}
