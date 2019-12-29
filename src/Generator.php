<?php

namespace Mtrajano\LaravelSwagger;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionMethod;

class Generator
{
    const OAUTH_TOKEN_PATH = '/oauth/token';
    const OAUTH_AUTHORIZE_PATH = '/oauth/authorize';

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
        $this->docParser = DocBlockFactory::createInstance();
    }

    public function generate()
    {
        $this->docs = $this->getBaseInfo();

        foreach ($this->getAppRoutes() as $route) {
            $this->originalUri = $uri = $this->getRouteUri($route);
            $this->uri = strip_optional_char($uri);

            if ($this->routeFilter && $this->isFilteredRoute()) {
                continue;
            }

            if ($this->config['parseSecurity'] && $this->isOauthRoute()) {
                $this->docs['securityDefinitions'] = $this->generateSecurityDefinitions();
            }

            $this->action = $route->getAction()['uses'];
            $methods = $route->methods();

            if (!isset($this->docs['paths'][$this->uri])) {
                $this->docs['paths'][$this->uri] = [];
            }

            foreach ($methods as $method) {
                $this->method = strtolower($method);

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
        return app('router')->getRoutes();
    }

    protected function getRouteUri(Route $route)
    {
        $uri = $route->uri();

        if (!Str::startsWith($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    protected function generateSecurityDefinitions()
    {
        $authFlow = $this->config['authFlow'];

        $this->validateAuthFlow($authFlow);

        $securityDefinition = [
            'OAuth2' => [
                'type' => 'oauth2',
                'flow' => $authFlow,
            ]
        ];


        if (in_array($authFlow, ['implicit', 'accessCode'])) {
            $securityDefinition['OAuth2']['authorizationUrl'] = $this->getEndpoint(self::OAUTH_AUTHORIZE_PATH);
        }

        if (in_array($authFlow, ['password', 'application', 'accessCode'])) {
            $securityDefinition['OAuth2']['tokenUrl'] = $this->getEndpoint(self::OAUTH_TOKEN_PATH);
        }

        $securityDefinition['OAuth2']['scopes'] = $this->generateOauthScopes();

        return $securityDefinition;
    }

    protected function generatePath()
    {
        $actionInstance = is_string($this->action) ? $this->getActionClassInstance($this->action) : null;
        $docBlock = $actionInstance ? ($actionInstance->getDocComment() ?: '') : '';

        [$isDeprecated, $summary, $description] = $this->parseActionDocBlock($docBlock);

        $this->docs['paths'][$this->uri][$this->method] = [
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
        if (!is_string($this->action)) {
            return false;
        }

        $parameters = $this->getActionClassInstance($this->action)->getParameters();

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
        return !preg_match('/^' . preg_quote($this->routeFilter, '/') . '/', $this->uri);
    }

    private function isOauthRoute()
    {
        return $this->uri === self::OAUTH_TOKEN_PATH || $this->uri === self::OAUTH_AUTHORIZE_PATH;
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
}
