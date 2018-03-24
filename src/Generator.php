<?php

namespace Mtrajano\LaravelSwagger;

use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Foundation\Http\FormRequest;

class Generator
{
    protected $config;

    protected $docs;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function generate()
    {
        $this->docs = $this->getBaseInfo();

        foreach ($this->getAppRoutes() as $route) {
            $uri = $this->getRouteUri($route);
            $methods = $route->methods();
            $action = $route->getAction('uses');

            if (!isset($this->docs['paths'][$uri])) {
                $this->docs['paths'][$uri] = [];
            }

            foreach ($methods as $method) {
                $this->generatePath($uri, $method, $action);
            }
        }

        return $this->docs;
    }

    protected function getBaseInfo()
    {
        return [
            'swagger' => '2.0',
            'info' => [
                'title' => $this->config['title'],
                'description' => $this->config['description'],
                'version' => $this->config['appVersion'],
            ],
            'host' => $this->config['host'],
            'basePath' => $this->config['basePath'],
            'paths' => [],
        ];
    }

    protected function getAppRoutes()
    {
        return app('routes')->get();
    }

    protected function getRouteUri(Route $route)
    {
        $uri = $route->uri();

        if (!starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    protected function generatePath($uri, $method, $action)
    {
        $method = strtolower($method);
        $this->docs['paths'][$uri][$method] = [
            'description' => strtoupper($method) . ' ' . $uri,
            'responses' => [
                '200' => [
                    'description' => 'OK'
                ]
            ],
        ];

        if ($rules = $this->getFormRules($action)) {
            $this->docs['paths'][$uri][$method]['parameters'] = $this->getActionParameters($method, $rules);
        }
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
        return in_array($method, ['get', 'head', 'delete']) ?
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
            //date, ip, email, etc..
            return 'string';
        }
    }

    protected function isParamRequired(array $paramRules)
    {
        return in_array('required', $paramRules);
    }
}