<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Class ProductController
 * @package Mtrajano\LaravelSwagger\Tests\Stubs\Controllers
 * @model \Mtrajano\LaravelSwagger\Tests\Stubs\Models\Product
 */
class ProductController extends Controller
{
    /**
     * @model \Mtrajano\LaravelSwagger\Tests\Stubs\Controllers\NotModelClass
     */
    public function index()
    {

    }

    /**
     * @param int $id
     * @model \Mtrajano\LaravelSwagger\Tests\Stubs\Models\Product
     */
    public function show(int $id)
    {

    }

    /**
     * @param Request $request
     */
    public function store(Request $request)
    {

    }
}

class NotModelClass
{

}