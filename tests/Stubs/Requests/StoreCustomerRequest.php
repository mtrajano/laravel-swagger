<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email',
        ];
    }
}
