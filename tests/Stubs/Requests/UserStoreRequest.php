<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'id'            => [
                'integer',
                'required'
            ],
            'email'         => 'required|email',
            'address'       => 'string|required',
            'dob'           => 'date|required',
            'picture'       => 'file',
            'is_validated'  => 'boolean',
            'score'         => 'numeric',
            'account_type'  => [
                'required',
                Rule::in(1,2)
            ],
            'language_spoken' => 'required|in:en,es'
        ];
    }
}
