<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Controllers;

use Illuminate\Routing\Controller;

class ApiController extends Controller
{
    public function index()
    {
        return json_encode(['result' => 'success']);
    }

    public function store()
    {
        return json_encode(['result' => 'success']);
    }
}
