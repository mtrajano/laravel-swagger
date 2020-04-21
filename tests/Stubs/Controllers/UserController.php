<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Controllers;

use Illuminate\Routing\Controller;
use Mtrajano\LaravelSwagger\Tests\Stubs\Requests\UserShowRequest;
use Mtrajano\LaravelSwagger\Tests\Stubs\Requests\UserStoreRequest;

class UserController extends Controller
{
    /** Get a list of of users in the application */
    public function index()
    {
        return json_encode([['first_name' => 'John'], ['first_name' => 'Jack']]);
    }

    /**
     * @tags user show
     */
    public function show(UserShowRequest $request, $id)
    {
        return json_encode(['first_name' => 'John']);
    }

    /**
     * Store a new user in the application
     *
     * Data is validated [see description here](https://example.com) so no bad data can be passed.
     * Please read the documentation for more information
     *
     * @param UserStoreRequest $request
     * @deprecated
     */
    public function store(UserStoreRequest $request)
    {
        return json_encode($request->all());
    }

    /**
     * @deprecated
     */
    public function details()
    {
        return json_encode([]);
    }
}
