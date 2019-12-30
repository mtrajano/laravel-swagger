<?php

namespace Mtrajano\LaravelSwagger;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionMethod;

class Generator
{
    const SECURITY_DEFINITION_NAME = 'OAuth2';
    const OAUTH_TOKEN_PATH = '/oauth/token';
    const OAUTH_AUTHORIZE_PATH = '/oauth/authorize';

    protected $config;
    protected $routeFilter;
    protected $docs;
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

    public function generate()
    {
        $this->docs = $this->getBaseInfo();

        if ($this->config['parseSecurity'] && $this->hasOauthRoutes()) {
            $this->docs['securityDefinitions'] = $this->generateSecurityDefinitions();
            $this->hasSecurityDefinitions = true;
        }

        foreach ($this->getAppRoutes() as $route) {
            $this->route = $route;

            if ($this->routeFilter && $this->isFilteredRoute()) {
                continue;
            }

            if (!isset($this->docs['paths'][$this->route->uri()])) {
                $this->docs['paths'][$this->route->uri()] = [];
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
        return array_map(function ($route) {
            return new DataObjects\Route($route);
        }, app('router')->getRoutes()->getRoutes());
    }

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

    protected function generatePath()
    {
        $actionInstance = is_string($this->route->action()) ? $this->getActionClassInstance($this->route->action()) : null;
        $docBlock = $actionInstance ? ($actionInstance->getDocComment() ?: '') : '';

        [$isDeprecated, $summary, $description] = $this->parseActionDocBlock($docBlock);

        $this->docs['paths'][$this->route->uri()][$this->method] = [
            'summary' => $summary,
            'description' => $description,
            'deprecated' => $isDeprecated,
            'responses' => [
                '200' => [
                    'description' => 'OK',
                ],
            ],
        ];

        $this->addActionParameters();

        if ($this->hasSecurityDefinitions) {
            $this->addActionScopes();
        }
    }

    protected function addActionParameters()
    {
        $rules = $this->getFormRules() ?: [];

        $parameters = (new Parameters\PathParameterGenerator($this->route->originalUri()))->getParameters();

        if (!empty($rules)) {
            $parameterGenerator = $this->getParameterGenerator($rules);

            $parameters = array_merge($parameters, $parameterGenerator->getParameters());
        }

        if (!empty($parameters)) {
            $this->docs['paths'][$this->route->uri()][$this->method]['parameters'] = $parameters;
        }
    }

    protected function addActionScopes()
    {
        foreach ($this->route->middleware() as $middleware) {
            if ($this->isPassportScopeMiddleware($middleware)) {
                $this->docs['paths'][$this->route->uri()][$this->method]['security'] = [
                    self::SECURITY_DEFINITION_NAME => $middleware->parameters(),
                ];
            }
        }
    }

    protected function getFormRules()
    {
        if (!is_string($this->route->action())) {
            return false;
        }

        $parameters = $this->getActionClassInstance($this->route->action())->getParameters();

        foreach ($parameters as $parameter) {
            $class = (string) $parameter->getClass();

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
        } catch (\Exception $e) {
            return [false, '', ''];
        }
    }

    private function isFilteredRoute()
    {
        return !preg_match('/^' . preg_quote($this->routeFilter, '/') . '/', $this->route->uri());
    }

    /**
     * Assumes routes have been created using Passport::routes().
     */
    private function hasOauthRoutes()
    {
        foreach ($this->getAppRoutes() as $route) {
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

        $scopes = \Laravel\Passport\Passport::scopes()->toArray();

        return array_combine(array_column($scopes, 'id'), array_column($scopes, 'description'));
    }

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
}
