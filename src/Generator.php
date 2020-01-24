<?php

namespace Mtrajano\LaravelSwagger;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\DefinitionGenerator;
use Mtrajano\LaravelSwagger\Responses\ResponseGenerator;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionException;
use ReflectionMethod;

class Generator
{
    const SECURITY_DEFINITION_NAME = 'OAuth2';
    const OAUTH_TOKEN_PATH = '/oauth/token';
    const OAUTH_AUTHORIZE_PATH = '/oauth/authorize';

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

    public function __construct($config, $routeFilter = null)
    {
        $this->config = $config;
        $this->routeFilter = $routeFilter;
        $this->docParser = DocBlockFactory::createInstance();
        $this->hasSecurityDefinitions = false;
    }

    /**
     * @return array
     * @throws LaravelSwaggerException
     */
    public function generate()
    {
        $this->docs = $this->getBaseInfo();
        $this->docs['definitions'] = [];

        if ($this->config['parseSecurity'] && $this->hasOauthRoutes()) {
            $this->docs['securityDefinitions'] = $this->generateSecurityDefinitions();
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

    protected function getBaseInfo()
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
        $authFlow = $this->config['authFlow'];

        $this->validateAuthFlow($authFlow);

        $securityDefinition = [
            self::SECURITY_DEFINITION_NAME => [
                'type' => 'oauth2',
                'flow' => $authFlow,
            ],
        ];

        if (in_array($authFlow, ['implicit', 'accessCode'])) {
            $securityDefinition[self::SECURITY_DEFINITION_NAME]['authorizationUrl'] = $this->getEndpoint(self::OAUTH_AUTHORIZE_PATH);
        }

        if (in_array($authFlow, ['password', 'application', 'accessCode'])) {
            $securityDefinition[self::SECURITY_DEFINITION_NAME]['tokenUrl'] = $this->getEndpoint(self::OAUTH_TOKEN_PATH);
        }

        $securityDefinition[self::SECURITY_DEFINITION_NAME]['scopes'] = $this->generateOauthScopes();

        return $securityDefinition;
    }

    /**
     * @throws ReflectionException
     */
    protected function generatePath()
    {
        $actionInstance = is_string($this->route->action()) ? $this->getActionClassInstance($this->route->action()) : null;
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
            $this->addActionScopes();
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

    protected function addActionScopes()
    {
        foreach ($this->route->middleware() as $middleware) {
            if ($this->isPassportScopeMiddleware($middleware)) {
                $this->docs['paths'][$this->getRouteUri()][$this->method]['security'] = [
                    self::SECURITY_DEFINITION_NAME => $middleware->parameters(),
                ];
            }
        }
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    protected function getFormRules()
    {
        if (!is_string($this->route->action())) {
            return false;
        }

        $parameters = $this->getActionClassInstance($this->route->action())->getParameters();

        foreach ($parameters as $parameter) {
            $reflectionClass = $parameter->getClass();
            if (!$reflectionClass) {
                continue;
            }

            $class = $reflectionClass->getName();

            if (is_subclass_of($class, FormRequest::class)) {
                return (new $class)->rules();
            }
        }

        return false;
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
     * @param string $action
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    private function getActionClassInstance(string $action)
    {
        [$class, $method] = Str::parseCallback($action);

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

    /**
     * Assumes routes have been created using Passport::routes().
     */
    private function hasOauthRoutes()
    {
        foreach ($this->getAllAppRoutes() as $route) {
            $uri = $route->uri();

            if ($uri === self::OAUTH_TOKEN_PATH || $uri === self::OAUTH_AUTHORIZE_PATH) {
                return true;
            }
        }

        return false;
    }

    private function getEndpoint(string $path)
    {
        return rtrim($this->config['host'], '/') . $path;
    }

    private function generateOauthScopes()
    {
        if (!class_exists('\Laravel\Passport\Passport')) {
            return [];
        }

        $scopes = Passport::scopes()->toArray();

        return array_combine(array_column($scopes, 'id'), array_column($scopes, 'description'));
    }

    /**
     * @param string $flow
     * @throws LaravelSwaggerException
     */
    private function validateAuthFlow(string $flow)
    {
        if (!in_array($flow, ['password', 'application', 'implicit', 'accessCode'])) {
            throw new LaravelSwaggerException('Invalid OAuth flow passed');
        }
    }

    private function isPassportScopeMiddleware(DataObjects\Middleware $middleware)
    {
        $resolver = $this->getMiddlewareResolver($middleware->name());

        return $resolver === 'Laravel\Passport\Http\Middleware\CheckScopes' ||
               $resolver === 'Laravel\Passport\Http\Middleware\CheckForAnyScope';
    }

    private function getMiddlewareResolver(string $middleware)
    {
        $middlewareMap = app('router')->getMiddleware();

        return $middlewareMap[$middleware] ?? null;
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
        return Str::replaceFirst($this->config['basePath'], '', $this->route->uri());
    }
}
