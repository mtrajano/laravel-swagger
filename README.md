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
For the parameters, it will take into account wether the request is a GET/HEAD/DELETE 
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

Will be available automatically a route to check your docs. 
Just access the route `/docs` after generate the docs to show Swagger UI view. 

## Installation

The package can easily be installed by running `composer require mtrajano/laravel-swagger` in your project's root folder.

If you are running a version of Laravel < 5.5 also make sure you add `Mtrajano\LaravelSwagger\SwaggerServiceProvider::class` to the `providers` array in `config/app.php`.

This will register the artisan command that will be available to you.

You can also override the default config provided by the application by running `php artisan vendor:publish --provider "Mtrajano\LaravelSwagger\SwaggerServiceProvider"` in your projects root and change the configuration in the new `config/laravel-swagger.php` file created.

## Usage

Generating the swagger documentation is easy, simply run `php artisan laravel-swagger:generate` in your project root.

The command will generate the swagger docs to API default version defined on `config/laravel-swagger.php` file.

To generate the docs for all versions you can run `php artisan laravel-swagger:generate --all-versions`.

You can still generate the docs to specific version passing the parameter `--version=`. E.g.:

```shell script
php artisan laravel-swagger:generate --version=2.0.0
``` 

By default, laravel-swagger prints out the documentation in json format, if you want it in YAML format you can override the format using the `--format` flag. Make sure to have the yaml extension installed if you choose to do so.

Format options are:
- `json`
- `yaml`

If you changes the default format on docs generation, you must change the format in `file_path` in `config/laravel-swagger.php`.

After generate the docs access the route `/docs` to see the API docs. 
The default version of the API will be shown, but you can choose the version on screen
or passing the version on route path. E.g.: `/docs/2.0.0`. 

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

Running `php artisan laravel-swagger:generate > swagger.json` will generate the following file:
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

You can define the annotation `@model` in your method, or a global on controller. 
It says that the action refs the model in your response. E.g.:

```php
// Model definition on method:
class OrderController
{
    /**
     * @param int $id
     * @model App\Order
     */
    public function show(int $id)
    {
        // ...
    }
}

// Model definition on Controller:
/**
 * Class ProductController
 * @model App\Product
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

The model definition fields will be obtained from `table columns` returned by 
`Schema::getColumnListing($model->getTable())` function. 

If you want use the fields from `$appends` attribute, create a method `getAppends()` in your 
model class returning the `$appends` attribute content. E.g.:

```php
public function getAppends(): array
{
    return $this->appends;
}
```

If exists a [factory](https://laravel.com/docs/master/database-testing#writing-factories) defined to Models, will be
generated fake data and added to `example` field on properties.

The columns will be filtered to remove fields on `$hidden` attribute. 
The model relationships will be added to definitions too.
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
class Order extends Model
{
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
        return '$ '.$this->value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ** Implements this method to use fields from $appends on model definition. ** 
    public function getAppends(): array
    {
        return $this->appends;
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

    public function orders()
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
If no one cast was defined, the default type `string` will be used.

## Responses

The responses will be defined based on routes http methods, `@throws` annotations and `auth` middleware. E.g.:

```php
// Routes
Route::get('/customers', 'CustomerController@index')
    ->name('customers.index')
    ->middleware('auth:jwt');

Route::get('/customers', 'CustomerController@store')
    ->name('customers.store');

Route::put('/customers/{id}', 'CustomerController@update')
    ->name('customers.update');

// Controller
/**
 * Class CustomerController
 * @model App\Customer
 */
class CustomerController extends Controller
{
    public function index()
    {

    }

    public function store(UpdateCustomerRequest $request)
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

## Complete Example

Routes:

```php
Route::post('/customers', 'CustomerController@store')->name('customers.store');
Route::put('/customers/{id}', 'CustomerController@update')->name('customers.update');
```

Controller:

```php
/**
 * Class CustomerController
 * @model App\Customer
 */
class CustomerController extends Controller
{
    /**
     * Store new customer.
     *
     * @param UpdateCustomerRequest $request
     */
    public function store(UpdateCustomerRequest $request)
    {
        // Store customer...
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
        // Update customer...
    }
}
```

Migrations:

```php
Schema::create('customers', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name');
    $table->string('email');
    $table->timestamps();
});
```

Models:

```php
class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
    ];

    protected $casts = [
        'name' => 'string',
        'email' => 'string',
    ];
}
```

Generated swagger:

```json
{
  "swagger": "2.0",
  "info": {
    "title": null,
    "description": "",
    "version": "1.0.0"
  },
  "host": "https://example.com",
  "basePath": "/",
  "paths": {
    "/customers": {
      "post": {
        "summary": "Store new customer.",
        "description": "",
        "deprecated": false,
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
    },
    "/customers/{id}": {
      "put": {
        "summary": "Update customer data.",
        "description": "Find customer by id and update it from data received from request.",
        "deprecated": false,
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
        },
        "parameters": [
          {
            "in": "path",
            "name": "id",
            "type": "string",
            "required": true,
            "description": ""
          }
        ]
      }
    }
  },
  "definitions": {
    "Customer": {
      "type": "object",
      "properties": {
        "id": {
          "type": "integer"
        },
        "name": {
          "type": "string"
        },
        "email": {
          "type": "string"
        },
        "created_at": {
          "type": "string",
          "format": "date-time"
        },
        "updated_at": {
          "type": "string",
          "format": "date-time"
        }
      }
    }
  }
}
```