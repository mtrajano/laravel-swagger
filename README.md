# Laravel Swagger

This package scans your laravel project's routes and auto generates a Swagger 2.0 documentation for you. If you inject Form Request classes in your controller's actions as request validation, it will also generate the parameters for each request that has them. It will take into account wether the request is a GET/HEAD/DELETE or a POST/PUT/PATCH request and make its best guess as to the type of parameter object it should generate. It will also generate the path parameters if you route contains them.

## Installation

The package can easily be installed by running `composer require mtrajano/laravel-swagger` in your project's root folder.

If you are running a version of Laravel < 5.5 also make sure you add `Mtrajano\LaravelSwagger\SwaggerServiceProvider::class` to the `providers` array in `config/app.php`.

This will register the artisan command that will be available to you.

You can also override the default config provided by the application by running `php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"` in your projects root and change the configuration in the new `config/laravel-swagger.php` file created.

## Usage

Generating the swagger documentation is easy, simply run `php artisan laravel-swagger:generate` in your project root. Keep in mind the command will simply print out the output in your console. If you want the docs saved in a file you can reroute the output like so: `php artisan laravel-swagger:generate > swagger.json`

If you wish to generate docs for a subset of your routes, you can pass a filter using `--filter`, for example: `php artisan laravel-swagger:generate --filter="/api"`

By default, laravel-swagger prints out the documentation in json format, if you want it in YAML format you can override the format using the `--format` flag. Make sure to have the yaml extension installed if you choose to do so.

Format options are:<br>
`json`<br>
`yaml`

## Example

Say you have a route `/api/users/{id}` that maps to `UserController@show`

Your sample controller might look like this:
```php
class UserController extends Controller
{
    public function show(UserShowRequest $request, $id)
    {
        return User::find($id);
    }
}
```

And the FormRequest class might look like this:
```php
class UserShowRequest extends FormRequest
{
    public function rules()
    {
        return [
            'fields' => 'array'
            'show_relationships' => 'boolean|required'
        ];
    }
}

```

Running `php artisan laravel-swagger:generate > swagger.json` will generate the following file:
```js
{
    "swagger": "2.0",
    "info": {
        "title": "Laravel",
        "description": "Test",
        "version": "1.0.1"
    },
    "host": "http:\/\/localhost",
    "basePath": "\/",
    "paths": {
        "\/api\/user\/{id}": {
            "get": {
                "description": "GET \/api\/user\/{id}",
                "responses": {
                    "200": {
                        "description": "OK"
                    }
                },
                "parameters": [
                    {
                        "in": "path",
                        "name": "id",
                        "type": "integer",
                        "required": true,
                        "description": ""
                    },
                    {
                        "in": "query",
                        "name": "fields",
                        "type": "array",
                        "required": false,
                        "description": ""
                    },
                    {
                        "in": "query",
                        "name": "show_relationships",
                        "type": "boolean",
                        "required": true,
                        "description": ""
                    }
                ]
            },
            ...
        }
    }
}
```