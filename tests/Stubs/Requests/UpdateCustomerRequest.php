<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email',
        ];
    }
}
