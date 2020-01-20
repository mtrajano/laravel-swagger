<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Controllers;

use Illuminate\Routing\Controller;
use Mtrajano\LaravelSwagger\Tests\Stubs\Requests\UpdateCustomerRequest;

/**
 * Class CustomerController
 * @package Mtrajano\LaravelSwagger\Tests\Stubs\Controllers
 * @model Mtrajano\LaravelSwagger\Tests\Stubs\Models\Customer
 */
class CustomerController extends Controller
{
    public function index()
    {

    }

    /**
     * Store new customer.
     *
     * @param UpdateCustomerRequest $request
     */
    public function store(UpdateCustomerRequest $request)
    {

    }

    /**
     * Update customer data.
     *
     * Find customer by id and update it from data received from request.
     *
     * @param int $id
     * @param UpdateCustomerRequest $request
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(int $id, UpdateCustomerRequest $request)
    {

    }

    /**
     * @param int $id
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function destroy(int $id)
    {

    }

    public function show(int $id)
    {

    }
}
