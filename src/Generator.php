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

            $this->action = $route->getAction('uses');
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
        $requestMethod = strtoupper($this->method);
        $actionInstance = $this->action ? $this->getActionClassInstance($this->action) : null;
        $docBlock = $actionInstance ? ($actionInstance->getDocComment() ?: "") : "";

        list($isDeprecated, $description) = $this->parseActionDocBlock($docBlock);

        $this->docs['paths'][$this->uri][$this->method] = [
            'summary' => "$requestMethod {$this->uri}",
            'description' => $description,
            'deprecated' => $isDeprecated,
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

        $parameters = $this->getActionClassInstance($this->action)->getParameters();

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

    private function getActionClassInstance(string $action)
    {
        list($class, $method) = Str::parseCallback($action);

        return new ReflectionMethod($class, $method);
    }

    private function parseActionDocBlock(string $docBlock)
    {
        $isDeprecated = !!preg_match('/@deprecated/', $docBlock);

        preg_match('/\/\*\s*(.+?)\s*\*\//sm', $docBlock, $matches);
        $commentWithoutTrailingCharacters = isset($matches[1]) ? $matches[1] : "";

        if (!$this->config['parseDescriptions']) {
            $commentWithoutTrailingCharacters = "";
        }

        if (empty($commentWithoutTrailingCharacters)) {
            return [$isDeprecated, $commentWithoutTrailingCharacters];
        }

        $lines = explode("\n", $commentWithoutTrailingCharacters);
        $lines = array_map('trim', $lines);

        $description = $this->buildDescription($lines);

        return [$isDeprecated, $description];
    }

    private function buildDescription(array $matches)
    {
        $commentLines = $this->getNonAnnotationLines($matches);
        $commentLines = $this->trimLines($commentLines);
        $commentLines = $this->trimAroundComment($commentLines);

        return implode("\n", $commentLines);
    }

    private function getNonAnnotationLines(array $lines)
    {
        return array_filter($lines, function($line) {
            return !preg_match('/^\*+\s*@/', $line);
        });
    }

    private function trimLines(array $comments)
    {
        $comments = array_map(function($line) {
            preg_match('/^\**(.*)/', $line, $matches);

            return isset($matches[1]) ? $matches[1] : "\n";
        }, $comments);

        return array_map('trim', $comments);
    }

    private function trimAroundComment(array $lines)
    {
        foreach ($lines as $key => $value) {
            if (trim($value)) {
                break;
            }

            unset($lines[$key]);
        }

        foreach (array_reverse($lines, true) as $key => $value) {
            if (trim($value)) {
                break;
            }

            unset($lines[$key]);
        }

        return array_values($lines);
    }
}