# Laravel Swagger

Laravel Swagger scans your Laravel project's endpoints and auto generates a Swagger 2.0 documentation for you.

[![Build Status](https://travis-ci.org/mtrajano/laravel-swagger.svg?branch=master)](https://travis-ci.org/mtrajano/laravel-swagger)
[![Latest Stable Version](https://poser.pugx.org/mtrajano/laravel-swagger/v/stable)](https://packagist.org/packages/mtrajano/laravel-swagger)
[![License](https://poser.pugx.org/mtrajano/laravel-swagger/license)](https://packagist.org/packages/mtrajano/laravel-swagger)

## About

Laravel Swagger works based on recommended practices by Laravel.
It will parse your routes and generate a path object for each one.
If you inject Form Request classes in your controller's actions as request validation,
it will also generate the parameters for each request that has them.
For the parameters, it will take into account whether the request is a GET/HEAD/DELETE
or a POST/PUT/PATCH request and make its best guess as to the type of parameter object
it should generate. It will also generate the path parameters if your route contains them.
Finally, this package will also scan any documentation you have in your action methods and
add it as summary and description to that path, along with any appropriate annotations such
as @deprecated.

One thing to note is this library leans on being explicit.
It will choose to include keys even if they have a default.
For example it chooses to say a route has a deprecated value of false rather than leaving it out.
I believe this makes reading the documentation easier by not leaving important information out.
The file can be easily cleaned up afterwards if the user chooses to leave out the defaults.

By default a route with the generated docs will be available in the `/docs` route using the Swagger UI view.

## Installation

The package can easily be installed by running `composer require mtrajano/laravel-swagger` in your project's root folder.

If you are running a version of Laravel < 5.5 also make sure you add `Mtrajano\LaravelSwagger\SwaggerServiceProvider::class` to the `providers` array in `config/app.php`.

This will register the artisan command that will be available to you.

You can also override the default config provided by the application by running `php artisan vendor:publish --provider "Mtrajano\LaravelSwagger\SwaggerServiceProvider"` in your projects root and change the configuration in the new `config/laravel-swagger.php` file created.

## Configuration

In the file `config/laravel-swagger.php` you can define your api versions, just copy the default and update according to your needs. Ex:
```php
[
    // ...

    'versions' => [
        [
            'appVersion' => '1.0.0',
            'host' => 'v1.myexample.com',
            'basePath' => '/v1',
            'schemes' => [
                'https',
            ],
            'consumes' => [
                'application/json',
            ],
            'produces' => [
                'application/json',
            ],
            'ignoredMethods' => [
                'head',
            ],
            'ignoredRoutes' => [
                'laravel-swagger.docs',
                'laravel-swagger.asset'
            ],
            'authFlow' => 'accessCode',
            'security_definition_type' => 'oauth2',
            'file_format' => 'json',
            'errors_definitions' => [
                'UnprocessableEntity' => [
                    'http_code' => 422,
                    'exception' => ValidationException::class,
                    'handler' => ValidationErrorDefinitionHandler::class
                ],
                'Forbidden' => [
                    'http_code' => 403,
                    'exception' => AuthorizationException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
                'NotFound' => [
                    'http_code' => 404,
                    'exception' => ModelNotFoundException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
                'Unauthenticated' => [
                    'http_code' => 401,
                    'exception' => AuthenticationException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
            ],
        ],
        [
            'appVersion' => '2.0.0',
            'host' => 'v2.myexample.com',
            'basePath' => '/v2',
            'schemes' => [
                'https',
            ],
            'consumes' => [
                'application/json',
            ],
            'produces' => [
                'application/json',
            ],
            'ignoredMethods' => [
                'head',
            ],
            'ignoredRoutes' => [
                'laravel-swagger.docs',
                'laravel-swagger.asset'
            ],
            'authFlow' => 'accessCode',
            'security_definition_type' => 'jwt',
            'file_format' => 'json',
            'errors_definitions' => [
                'UnprocessableEntity' => [
                    'http_code' => 422,
                    'exception' => ValidationException::class,
                    'handler' => ValidationErrorDefinitionHandler::class
                ],
                'Forbidden' => [
                    'http_code' => 403,
                    'exception' => AuthorizationException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
                'NotFound' => [
                    'http_code' => 404,
                    'exception' => ModelNotFoundException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
                'Unauthenticated' => [
                    'http_code' => 401,
                    'exception' => AuthenticationException::class,
                    'handler' => DefaultErrorDefinitionHandler::class
                ],
            ],
        ],
    ],
];
```

## Usage

In order for the definition generation to work you will need migrate your database tables. Make sure to execute the `migrate` command before generating the docs:

```shell script
php artisan migrate
```

Generating the swagger documentation is easy, simply run `php artisan laravel-swagger:generate` in your project root.

By default, the command will generate the docs for all versions defined in `config/laravel-swagger.php`.

You can filter to a specific version by passing the parameter `--api-version=`. E.g.:

```shell script
php artisan laravel-swagger:generate --api-version=2.0.0 # must match the version defined in the configs
```

By default, laravel-swagger generates the documentation in json format, if you want it in YAML format you can override the format using the `--format` flag. Make sure to have the yaml extension installed if you choose to do so.

Supported format options are:
- `json`
- `yaml`

After generating the docs access the route `/docs` to see the API docs.
The latest version of the API will be shown by default, but you can choose the version on screen or by passing the version on path param. E.g.: `/docs/2.0.0`.

## Example

Say you have a route `/api/users/{id}` that maps to `UserController@show`

Your sample controller might look like this:
```php
/**
 * Return all the details of a user
 *
 * Returns the user's first name, last name and address
 * Please see the documentation [here](https://example.com/users) for more information
 *
 * @deprecated
 */
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
            'fields' => 'array',
            'show_relationships' => 'boolean|required'
        ];
    }
}

```

Running `php artisan laravel-swagger:generate` will generate a file on the public path with the following definitions:
```json
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
                "summary": "Return all the details of a user",
                "description": "Returns the user's first name, last name and address. Please see the documentation [here](https://example.com/users) for more information",
                "deprecated": true
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

## Definitions

You can define the annotation `@model` with the full model path in the method or controller/class docs (for all methods). This will generate a ref to the model in
the response, ex:

```php
// Model definition on method:
class OrderController
{
    /**
     * @param int $id
     * @model App\Models\Order
     */
    public function show(int $id)
    {
        // ...
    }
}

// Model definition on Controller:
/**
 * Class ProductController
 * @model App\Models\Product
 */
class ProductController
{
    /**
     * @param int $id
     */
    public function show(int $id)
    {
        // ...
    }
}
```

### Model

The model definition fields will be obtained from table `columns` returned by
`Schema::getColumnListing($model->getTable())` function.

If you want use the fields from `$appends` attribute, use the trait 'Mtrajano\LaravelSwagger\Traits\HasAppends' in your
model class. E.g.:

```php
use Mtrajano\LaravelSwagger\Traits\HasAppends;

class MyModel extends Model
{
    use HasAppends;

    // ...
}
```

If a model has an associated [factory](https://laravel.com/docs/master/database-testing#writing-factories), and you enable the `generateExampleData` option in the configs, `example` data will also be generated for each of the fields defined.

WARNING: We use database transactions to generate this data.
Although the data will not be saved to the database, it is recommended that if you use this functionality that you do so on a dev environment in order to avoid any unexpected side effects.

The columns will be filtered to remove fields on `$hidden` attribute.

If you enable the `parseModelRelationships` option, and if the relationship methods contain `Relationship` return typehints, the model relationships will be added to definitions as well.

WARNING: In order to get the associated model of the relationship we must invoke the method.
Make sure there are no side effects when calling these methods and all they do is return the relationship.

E.g.:

```php
// Tables structure:
Schema::create('products', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name');
    $table->decimal('price');
    $table->boolean('active');
    $table->timestamp('finished_at');
    $table->timestamps();
});
Schema::create('orders', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->decimal('value');
    $table->unsignedBigInteger('product_id');
    $table->foreign('product_id')
        ->references('id')
        ->on('products');
    $table->timestamps();
});

// Models
use Illuminate\Database\Eloquent\Relations;
use Mtrajano\LaravelSwagger\Traits\HasAppends;

class Order extends Model
{
    use HasAppends;

    protected $fillable = [
        'value',
    ];

    protected $casts = [
        'value' => 'float',
        'formatted_value' => 'string',
    ];

    protected $appends = [
        'formatted_value',
    ];

    public function getFormattedValueAttribute()
    {
        return '$ ' . $this->value;
    }

    public function product() : Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'active',
    ];

    protected $hidden = [
        'active',
    ];

    protected $casts = [
        'name' => 'string',
        'price' => 'float',
        'active' => 'boolean',
    ];

    protected $dates = [
        'finished_at',
    ];

    public function orders() : Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }
}
```

The structure above will return the following definitions:

```json
{
  "Order": {
    "type": "object",
    "properties": {
      "id": {
        "type": "integer",
        "example": "1"
      },
      "value": {
        "type": "number",
        "format": "float",
        "example": "16.54"
      },
      "product_id": {
        "type": "string"
      },
      "customer_id": {
        "type": "string"
      },
      "created_at": {
        "type": "string",
        "format": "date-time"
      },
      "updated_at": {
        "type": "string",
        "format": "date-time"
      },
      "formatted_value": {
        "type": "string"
      },
      "product": {
        "$ref": "#/definitions/Product"
      }
    }
  },
  "Product": {
    "type": "object",
    "properties": {
      "id": {
        "type": "integer"
      },
      "name": {
        "type": "string"
      },
      "price": {
        "type": "number",
        "format": "float"
      },
      "finished_at": {
        "type": "string",
        "format": "date-time"
      },
      "created_at": {
        "type": "string",
        "format": "date-time"
      },
      "updated_at": {
        "type": "string",
        "format": "date-time"
      },
      "orders": {
        "type": "array",
        "items": {
          "$ref": "#/definitions/Order"
        }
      }
    }
  }
}
```

The attribute `$casts` will be used to define the properties `type` and `format`.
If no one cast is defined, the default type `string` will be used.

## Responses

The responses will be defined based on routes http methods, `@throws` annotations and `auth` middleware. E.g.:

```php
// Routes
Route::get('/customers', 'CustomerController@index')
    ->name('customers.index')
    ->middleware('auth:api');

Route::get('/customers', 'CustomerController@store')
    ->name('customers.store');

Route::put('/customers/{id}', 'CustomerController@update')
    ->name('customers.update');

// Controller
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;

/**
 * Class CustomerController
 * @model App\Customer
 */
class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {

    }

    public function store(StoreCustomerRequest $request)
    {

    }

    /**
     * @param int $id
     * @param UpdateCustomerRequest $request
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(int $id, UpdateCustomerRequest $request)
    {
        // ...
    }
}
```

The definitions above will generate the following responses:

Get All Customers:

```json
{
  "responses": {
     "200": {
       "description": "OK",
       "schema": {
         "type": "array",
         "items": {
           "$ref": "#/definitions/Customer"
         }
       }
     },
     "401": {
       "description": "Unauthenticated"
     }
  }
}
```

Store a Customer:

```json
{
  "responses": {
     "201": {
       "description": "Created",
       "schema": {
         "$ref": "#/definitions/Customer"
       }
     },
     "422": {
       "description": "Validation errors"
     }
  }
}
```

Update Customer:

```json
{
  "responses": {
    "204": {
      "description": "No Content"
    },
    "422": {
      "description": "Validation errors"
    },
    "404": {
      "description": "Model not found"
    },
    "401": {
      "description": "Unauthenticated"
    },
    "403": {
      "description": "Forbidden"
    }
  }
}
```
