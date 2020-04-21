<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\DefaultErrorDefinitionHandler;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\ValidationErrorDefinitionHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Basic Info
    |--------------------------------------------------------------------------
    |
    | The basic info for the application such as the title description,
    | description.
    |
    */

    'title' => env('APP_NAME'),

    'description' => '',

    'schemes' => [
        // 'http',
        // 'https',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parse summary and descriptions
    |--------------------------------------------------------------------------
    |
    | If true will parse the action method docBlock and make its best guess
    | as to what is the summary and description. Usually the first line will be
    | used as the route's summary and any paragraphs below (other than
    | annotations) will be used as the description. It will also parse any
    | appropriate annotations, such as @deprecated.
    |
    */

    'parseDocBlock' => true,

    /*
    |--------------------------------------------------------------------------
    | Generate tags from controller name
    |--------------------------------------------------------------------------
    |
    | If true creates a tag for an action from its controller name.
    | (e.g. Methods in the UserController will be tagged with "User")
    | This option does not overwrite any user specified tags on methods.
    | Add "@tags tagName1 tagName2" to a methods phpDoc to use custom tags
    |
    */

    'autoTags' => false,

    /*
    |--------------------------------------------------------------------------
    | Parse security
    |--------------------------------------------------------------------------
    |
    | Tries to parse your middleware and defines the security definitions of
    | the application
    |
    */

    'parseSecurity' => true,

    /*
    |--------------------------------------------------------------------------
    | Docs Route
    |--------------------------------------------------------------------------
    |
    | The route definitions that will be used to show the docs.
    |
    */

    'route' => [
        'path' => '/docs/{version?}',
        'name' => 'laravel-swagger.docs',
        'middleware' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Danger zone
    |--------------------------------------------------------------------------
    |
    | It is highly recommended that if you turn these configurations on, that you
    | do not run this on a production environment.
    |
    | The `generateExampleData` flag is used to generate the example data for your
    | parameters, if the parameter is part of a model that has a Factory associated
    | with it. This will create an instance of the model between a transaction and
    | then roll it back.
    |
    | The `parseModelRelationships` flag is used to generate the model definitions along with
    | their relationships. In order to generate the relationships we need to call
    | the relationship methods to get the associated model. Only turn this
    | configuration on if there is no side effect in calling these methods.
    |
    */

    'generateExampleData' => false,

    'parseModelRelationships' => false,

    /*
    |--------------------------------------------------------------------------
    | Docs Versions Config
    |--------------------------------------------------------------------------
    |
    | The versions arrays must be incremented whenever you want to create a new
    | API version.  You can define the specific configuration for each version
    | of you API.
    |
    */
    'versions' => [
        [
            'appVersion' => '1.0.0',

            'host' => env('APP_URL'),

            'basePath' => '/',

            'consumes' => [
                // 'application/json',
            ],

            'produces' => [
                // 'application/json',
            ],

            /*
            |--------------------------------------------------------------------------
            | Ignores
            |--------------------------------------------------------------------------
            |
            | Methods and routes in the following array will be ignored in the paths array
            |
            */

            'ignoredMethods' => [
                'head',
            ],

            'ignoredRoutes' => [
                'laravel-swagger.docs',
                'laravel-swagger.asset',
            ],

            /*
            |--------------------------------------------------------------------------
            | Security
            |--------------------------------------------------------------------------
            |
            | If your application uses Laravel's Passport package with the recommended
            | settings, Laravel Swagger will attempt to parse your settings and
            | automatically generate the securityDefinitions along with the operation
            | object's security parameter, you may turn off this behavior with parseSecurity.
            |
            | Possible values for flow: ["implicit", "password", "application", "accessCode"]
            | See https://medium.com/@darutk/diagrams-and-movies-of-all-the-oauth-2-0-flows-194f3c3ade85
            | for more information.
            |
            */

            'authFlow' => 'accessCode',

            /*
            |--------------------------------------------------------------------------
            | Security Definitions Type
            |--------------------------------------------------------------------------
            |
            | You must define the security definition type. You can choose
            | between "jwt" and "oauth2".
            |
            */

            'security_definition_type' => 'oauth2',

            /*
            |------------------------------------------------------------------
            | File format
            |------------------------------------------------------------------
            |
            | The "format" must be exactly the same from docs generation. E.g.:
            |
            | If you used the following command to generate:
            | `php artisan laravel-swagger:generate --format=yaml`
            | the format must be "yaml".
            |
            */

            'file_format' => 'json',

            /*
            |------------------------------------------------------------------
            | Errors Definitions
            |------------------------------------------------------------------
            |
            | The classes that will handle with errors definitions. The array
            | key is the name of the definition. The "http_code" will be used
            | to link the definition with the error. The "exception" with be
            | used to get the "handler". The definitions generator will get
            | the exceptions from DocBlock, middlewares and actions parameters.
            | According to exception an specific handler will be called.
            | To return your own errors response definitions you can create a
            | handler and define here.
            |
            */

            'errors_definitions' => [
                'UnprocessableEntity' => [
                    'http_code' => 422,
                    'exception' => ValidationException::class,
                    'handler' => ValidationErrorDefinitionHandler::class,
                ],
                'Forbidden' => [
                    'http_code' => 403,
                    'exception' => AuthorizationException::class,
                    'handler' => DefaultErrorDefinitionHandler::class,
                ],
                'NotFound' => [
                    'http_code' => 404,
                    'exception' => ModelNotFoundException::class,
                    'handler' => DefaultErrorDefinitionHandler::class,
                ],
                'Unauthenticated' => [
                    'http_code' => 401,
                    'exception' => AuthenticationException::class,
                    'handler' => DefaultErrorDefinitionHandler::class,
                ],
            ],
        ],
    ],
];
