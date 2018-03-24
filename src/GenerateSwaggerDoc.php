<?php

namespace Mtrajano\LaravelSwagger;

use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Foundation\Http\FormRequest;

class GenerateSwaggerDoc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-swagger:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generates a swagger documentation file for this application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $parsed = [
            'swagger' => '2.0',
            'info' => [
                'title' => config('laravel-swagger.title'),
                'description' => config('laravel-swagger.description'),
                'version' => config('laravel-swagger.appVersion'),
            ],
            'host' => config('laravel-swagger.host'),
            'basePath' => config('laravel-swagger.basePath'),
            'paths' => [],
        ];

        foreach (app('routes')->get() as $route) {
            $uri = $route->uri();
            $methods = $route->methods();
            $action = $route->getAction('uses');

            if (!starts_with($uri, '/')) {
                $uri = '/' . $uri;
            }

            if (!isset($parsed['paths'][$uri])) {
                $parsed['paths'][$uri] = [];
            }

            foreach ($methods as $method) {
                $method = strtolower($method);
                $parsed['paths'][$uri][$method] = [
                    'description' => strtoupper($method) . ' ' . $uri,
                    'responses' => [
                        '200' => [
                            'description' => 'OK'
                        ]
                    ],
                ];

                if ($rules = $this->getFormRules($action)) {
                    $parsed['paths'][$uri][$method]['parameters'] = $this->getActionParameters($method, $rules);
                }
            }
        }

        echo json_encode($parsed, JSON_PRETTY_PRINT) . "\n";
    }

    protected function getFormRules($action)
    {
        if (!is_string($action)) return false;

        $parsedAction = Str::parseCallback($action);

        $parameters = (new ReflectionMethod($parsedAction[0], $parsedAction[1]))->getParameters();

        foreach ($parameters as $parameter) {
            $class = (string) $parameter->getType();

            if (is_subclass_of($class, FormRequest::class)) {
                return (new $class)->rules();
            }
        }
    }

    protected function getActionParameters($method, $rules)
    {
        $params = [];

        foreach  ($rules as $param => $rule) {
            $paramRules = explode('|', $rule);

            $params[] = [
                'in' => $this->getParamLocation($method),
                'name' => $param,
                'type' => $this->getParamType($paramRules),
                'required' => $this->isParamRequired($paramRules),
                'description' => '',
            ];
        }

        return $params;
    }

    protected function getParamLocation($method)
    {
        return in_array($method, ['get', 'head']) ?
            'query':
            'body';
    }

    protected function getParamType(array $paramRules)
    {
        if (in_array('integer', $paramRules)) {
            return 'integer';
        } else if (in_array('numeric', $paramRules)) {
            return 'number';
        } else if (in_array('boolean', $paramRules)) {
            return 'boolean';
        } else if (in_array('array', $paramRules)) {
            return 'array';
        } else {
            //date, ip, file, etc..
            return 'string';
        }
    }

    protected function isParamRequired(array $paramRules)
    {
        return in_array('required', $paramRules);
    }
}