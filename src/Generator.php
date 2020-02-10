<?php

namespace Mtrajano\LaravelSwagger;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\DefinitionGenerator;
use Mtrajano\LaravelSwagger\Definitions\Security\SecurityDefinitionsFactory;
use Mtrajano\LaravelSwagger\Definitions\Security\Contracts\SecurityDefinitionsGenerator;
use Mtrajano\LaravelSwagger\Responses\ResponseGenerator;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionException;
use ReflectionMethod;

class Generator
{
    protected $config;
    protected $routeFilter;
    protected $docs;

    /**
     * @var Route|null
     */
    protected $route;
    protected $method;
    protected $docParser;
    protected $hasSecurityDefinitions;

    /**
     * @var SecurityDefinitionsGenerator|null
     */
    private $securityDefinitionGenerator;

    /**
     * Generator constructor.
     * @param $config
     * @param null $routeFilter
     * @throws LaravelSwaggerException
     */
    public function __construct($config, $routeFilter = null)
    {
        $this->config = $config;
        $this->routeFilter = $routeFilter;
        $this->docParser = DocBlockFactory::createInstance();
        $this->hasSecurityDefinitions = false;

        if ($this->config['parseSecurity']) {
            $this->securityDefinitionGenerator = SecurityDefinitionsFactory::createGenerator(
                $config['security_definition_type'],
                $config['authFlow']
            );
        }
    }

    /**
     * @return array
     * @throws LaravelSwaggerException
     * @throws ReflectionException
     */
    public function generate()
    {
        $this->docs = $this->getBaseStructure();

        $securityDefinitions = $this->generateSecurityDefinitions();
        if ($securityDefinitions) {
            $this->docs['securityDefinitions'] = $securityDefinitions;
            $this->hasSecurityDefinitions = true;
        }

        foreach ($this->getAppRoutes() as $route) {
            $this->route = $route;

            if (!isset($this->docs['paths'][$this->getRouteUri()])) {
                $this->docs['paths'][$this->getRouteUri()] = [];
            }

            foreach ($route->methods() as $method) {
                $this->method = $method;

                if (in_array($this->method, $this->config['ignoredMethods'])) {
                    continue;
                }

                $this->generatePath();
            }
        }

        return $this->docs;
    }

    protected function getBaseStructure()
    {
        $baseInfo = [
            'swagger' => '2.0',
            'info' => [
                'title' => $this->config['title'],
                'description' => $this->config['description'],
                'version' => $this->config['appVersion'],
            ],
            'host' => preg_replace('/^https?:\/\//', '', $this->config['host']),
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
        $baseInfo['definitions'] = [];

        return $baseInfo;
    }

    /**
     * @return Route[]
     */
    protected function getAppRoutes(): array
    {
        $allRoutes = $this->getAllAppRoutes();

        $routes = array_filter($allRoutes, function (Route $route) {
            return !in_array($route->getName(), $this->config['ignoredRoutes']);
        });

        if ($this->routeFilter) {
            $routes = array_filter($routes, function (Route $route) {
                return preg_match(
                    '/^' . // Starts with prefix
                    preg_quote($this->routeFilter, '/') .
                    '/',
                    $route->uri()
                );
            });
        }

        return $routes;
    }

    /**
     * @return Route[]
     */
    protected function getAllAppRoutes()
    {
        $routes = app('router')->getRoutes()->getRoutes();

        return array_map(function ($route) {
            return new DataObjects\Route($route);
        }, $routes);
    }

    /**
     * @return array
     * @throws LaravelSwaggerException
     */
    protected function generateSecurityDefinitions()
    {
        if (!$this->securityDefinitionGenerator) {
            return null;
        }

        return $this->securityDefinitionGenerator->generate();
    }

    /**
     * @throws ReflectionException
     */
    protected function generatePath()
    {
        $actionInstance = $this->getActionClassInstance();
        $docBlock = $actionInstance ? ($actionInstance->getDocComment() ?: '') : '';

        [$isDeprecated, $summary, $description] = $this->parseActionDocBlock($docBlock);

        $path = $this->getRouteUri();

        $this->docs['paths'][$path][$this->method] = [
            'summary' => $summary,
            'description' => $description,
            'deprecated' => $isDeprecated,
        ];

        $this->addActionDefinitions();

        $this->addActionResponses();

        $this->addActionParameters();

        if ($this->hasSecurityDefinitions) {
            $this->addRouteSecurityDefinitions();
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function addActionParameters()
    {
        $rules = $this->getFormRules() ?: [];

        $parameters = (new Parameters\PathParameterGenerator($this->route->originalUri()))->getParameters();

        if (!empty($rules)) {
            $parameterGenerator = $this->getParameterGenerator($rules);

            $parameters = array_merge($parameters, $parameterGenerator->getParameters());
        }

        if (!empty($parameters)) {
            $this->docs['paths'][$this->getRouteUri()][$this->method]['parameters'] = $parameters;
        }
    }

    protected function addRouteSecurityDefinitions()
    {
        $routeDefinitions = $this->securityDefinitionGenerator->generateForRoute($this->route);
        if ($routeDefinitions) {
            $this->docs['paths'][$this->getRouteUri()][$this->method]['security'] = $routeDefinitions;
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function getFormRules(): array
    {
        $action_instance = $this->getActionClassInstance();

        if (!$action_instance) {
            return [];
        }

        $parameters = $action_instance->getParameters();

        foreach ($parameters as $parameter) {
            $class = $parameter->getClass();

            if (!$class) {
                continue;
            }

            $class_name = $class->getName();

            if (is_subclass_of($class_name, FormRequest::class)) {
                return (new $class_name)->rules();
            }
        }

        return [];
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

    /**
     * @throws ReflectionException
     */
    private function getActionClassInstance(): ?ReflectionMethod
    {
        [$class, $method] = Str::parseCallback($this->route->action());

        if (!$class || !$method) {
            return null;
        }

        return new ReflectionMethod($class, $method);
    }

    private function parseActionDocBlock(string $docBlock)
    {
        if (empty($docBlock) || !$this->config['parseDocBlock']) {
            return [false, '', ''];
        }

        try {
            $parsedComment = $this->docParser->create($docBlock);

            $isDeprecated = $parsedComment->hasTag('deprecated');

            $summary = $parsedComment->getSummary();
            $description = (string) $parsedComment->getDescription();

            return [$isDeprecated, $summary, $description];
        } catch (Exception $e) {
            return [false, '', ''];
        }
    }

    private function addActionResponses()
    {
        $responses = (
            new ResponseGenerator($this->route, $this->config['errors_definitions'])
        )->generate();

        $this->docs['paths'][$this->getRouteUri()][$this->method]['responses'] = $responses;
    }

    /**
     * @throws ReflectionException
     */
    private function addActionDefinitions()
    {
        $this->docs['definitions'] += (
            new DefinitionGenerator($this->route, $this->config['errors_definitions'])
        )->generate();
    }

    private function getRouteUri()
    {
        $uri = Str::replaceFirst($this->config['basePath'], '', $this->route->uri());

        return Str::start($uri, '/');
    }
}
