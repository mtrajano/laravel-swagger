<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Basic Info
    |--------------------------------------------------------------------------
    |
    | The basic info for the application such as the title description,
    | description, version, etc...
    |
    */

    'title' => env('APP_NAME'),

    'description' => '',

    'appVersion' => '1.0.0',

    'host' => env('APP_URL'),

    'basePath' => '/',

    'schemes' => [
        // 'http',
        // 'https',
    ],

    'consumes' => [
        // 'application/json',
    ],

    'produces' => [
        // 'application/json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore methods
    |--------------------------------------------------------------------------
    |
    | Methods in the following array will be ignored in the paths array
    |
    */

    'ignoredMethods' => [
        'head',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parse summary and descriptions
    |--------------------------------------------------------------------------
    |
    | If true will parse the action method docBlock and make it's best guess
    | for what is the summary and description. Usually the first line will be
    | used as the route's summary and any paragraphs below (other than
    | annotations) will be used as the description. It will also parse any
    | appropriate annotations, such as @deprecated.
    |
    */

    'parseDocBlock' => true,

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

    'parseSecurity' => true,

    'authFlow' => 'accessCode',
];
