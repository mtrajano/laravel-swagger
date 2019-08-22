<?php

namespace Mtrajano\LaravelSwagger;

use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Foundation\Http\FormRequest;

class Generator
{
    protected $config;

    protected $routeFilter;

    protected $docs;

    protected $uri;

    protected $originalUri;

    protected $method;

    protected $action;

    public function __construct($config, $routeFilter = null)
    {
        $this->config = $config;
        $this->routeFilter = $routeFilter;
    }

    public function generate()
    {
        $this->docs = $this->getBaseInfo();

        foreach ($this->getAppRoutes() as $route) {
            $this->originalUri = $uri = $this->getRouteUri($route);
            $this->uri = strip_optional_char($uri);

            if ($this->routeFilter && !preg_match('/^' . preg_quote($this->routeFilter, '/') . '/', $this->uri)) {
                continue;
            }

            $this->action = $route->getAction()['uses'];
            $methods = $route->methods();

            if (!isset($this->docs['paths'][$this->uri])) {
                $this->docs['paths'][$this->uri] = [];
            }

            foreach ($methods as $method) {
                $this->method = strtolower($method);

                if (in_array($this->method, $this->config['ignoredMethods'])) continue;

                $this->generatePath();
            }
        }

        return $this->docs;
    }

    protected function getBaseInfo()
    {
        $baseInfo = [
            'swagger' => '2.0',
            'info' => [
                'title' => $this->config['title'],
                'description' => $this->config['description'],
                'version' => $this->config['appVersion'],
            ],
            'host' => $this->config['host'],
            'basePath' => $this->config['basePath'],
        ];

        if (!empty($this->config['schemes'])) {
            $baseInfo['schemes'] = $this->config['schemes'];
        }

        if (!empty($this->config['consumes'])) {
            $baseInfo['consumes'] = $this->config['consumes'];
        }

        if (!empty($this->config['produces'])) {
            $baseInfo['produces'] = $this->config['produces'];
        }

        $baseInfo['paths'] = [];

        return $baseInfo;
    }

    protected function getAppRoutes()
    {
        return app('router')->getRoutes();
    }

    protected function getRouteUri(Route $route)
    {
        $uri = $route->uri();

        if (!starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    protected function generatePath()
    {
        $methodDescription = strtoupper($this->method);

        $this->docs['paths'][$this->uri][$this->method] = [
            'description' => "$methodDescription {$this->uri}",
            'responses' => [
                '200' => [
                    'description' => 'OK'
                ]
            ],
        ];

        $this->addActionParameters();
    }

    protected function addActionParameters()
    {
        $rules = $this->getFormRules() ?: [];

        $parameters = (new Parameters\PathParameterGenerator($this->originalUri))->getParameters();

        if (!empty($rules)) {
            $parameterGenerator = $this->getParameterGenerator($rules);

            $parameters = array_merge($parameters, $parameterGenerator->getParameters());
        }

        if (!empty($parameters)) {
            $this->docs['paths'][$this->uri][$this->method]['parameters'] = $parameters;
        }
    }

    protected function getFormRules()
    {
        if (!is_string($this->action)) return false;

        $parsedAction = Str::parseCallback($this->action);

        $parameters = (new ReflectionMethod($parsedAction[0], $parsedAction[1]))->getParameters();

        foreach ($parameters as $parameter) {
            $class = (string) $parameter->getType();

            if (is_subclass_of($class, FormRequest::class)) {
                return (new $class)->rules();
            }
        }
    }

    protected function getParameterGenerator($rules)
    {
        switch ($this->method) {
            case 'post':
            case 'put':
            case 'patch':
                return new Parameters\BodyParameterGenerator($rules);
            default:
                return new Parameters\QueryParameterGenerator($rules);
        }
    }
}