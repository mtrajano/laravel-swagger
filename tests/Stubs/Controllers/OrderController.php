<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    /**
     * @model \Mtrajano\LaravelSwagger\Tests\Stubs\Models\Order
     */
    public function index()
    {

    }

    /**
     * @param Request $request
     */
    public function store(Request $request)
    {

    }

    /**
     * @param int $id
     * @model \Mtrajano\LaravelSwagger\Tests\Stubs\Models\Order
     */
    public function show(int $id)
    {

    }
}
