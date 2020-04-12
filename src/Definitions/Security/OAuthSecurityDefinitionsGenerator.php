<?php

namespace Mtrajano\LaravelSwagger\Definitions\Security;

use Laravel\Passport\Passport;
use Mtrajano\LaravelSwagger\DataObjects\Middleware;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\Security\Contracts\SecurityDefinitionsGenerator;
use Mtrajano\LaravelSwagger\LaravelSwaggerException;

class OAuthSecurityDefinitionsGenerator implements SecurityDefinitionsGenerator
{
    const SECURITY_DEFINITION_NAME = 'OAuth2';
    const OAUTH_TOKEN_PATH = '/oauth/token';
    const OAUTH_AUTHORIZE_PATH = '/oauth/authorize';

    /**
     * @var string
     */
    private $authFlow;

    /**
     * OAuthSecurityDefinitionsGenerator constructor.
     *
     * @throws LaravelSwaggerException
     */
    public function __construct(string $authFlow)
    {
        $this->setAuthFlow($authFlow);
    }

    /**
     * @throws LaravelSwaggerException
     */
    private function setAuthFlow(string $authFlow): void
    {
        if (!in_array($authFlow, ['password', 'application', 'implicit', 'accessCode'])) {
            throw new LaravelSwaggerException('Invalid OAuth flow passed');
        }

        $this->authFlow = $authFlow;
    }

    /**
     * @throws LaravelSwaggerException
     */
    public function generate(): array
    {
        if (!$this->hasOauthRoutes()) {
            throw new LaravelSwaggerException(
                'No one OAuth2 route defined. Please, call the method Passport::routes() in your Service Provider'
            );
        }

        $securityDefinition = [
            self::SECURITY_DEFINITION_NAME => [
                'type' => 'oauth2',
                'flow' => $this->authFlow,
            ],
        ];

        if (in_array($this->authFlow, ['implicit', 'accessCode'])) {
            $securityDefinition[self::SECURITY_DEFINITION_NAME]['authorizationUrl'] = $this->getEndpoint(self::OAUTH_AUTHORIZE_PATH);
        }

        if (in_array($this->authFlow, ['password', 'application', 'accessCode'])) {
            $securityDefinition[self::SECURITY_DEFINITION_NAME]['tokenUrl'] = $this->getEndpoint(self::OAUTH_TOKEN_PATH);
        }

        $securityDefinition[self::SECURITY_DEFINITION_NAME]['scopes'] = $this->generateOauthScopes();

        return $securityDefinition;
    }

    private function getEndpoint(string $path): string
    {
        $baseUrl = preg_replace(
            '/^https?:\/\//',
            '',
            rtrim(config('laravel-swagger.host'), '/')
        );

        return $baseUrl . $path;
    }

    private function generateOauthScopes(): array
    {
        if (!class_exists('\Laravel\Passport\Passport')) {
            return [];
        }

        $scopes = Passport::scopes()->toArray();

        return array_combine(array_column($scopes, 'id'), array_column($scopes, 'description'));
    }

    public function generateForRoute(Route $route): array
    {
        $actionScopes = [];
        foreach ($route->getMiddleware() as $middleware) {
            if ($this->isPassportScopeMiddleware($middleware)) {
                $actionScopes = [
                    self::SECURITY_DEFINITION_NAME => $middleware->parameters(),
                ];
            }
        }

        return $actionScopes;
    }

    private function isPassportScopeMiddleware(Middleware $middleware): bool
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

    /**
     * Assumes routes have been created using Passport::routes().
     */
    private function hasOauthRoutes(): bool
    {
        foreach ($this->getAllAppRoutes() as $route) {
            $uri = $route->getUri();

            if ($uri === self::OAUTH_TOKEN_PATH || $uri === self::OAUTH_AUTHORIZE_PATH) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Route[]
     */
    protected function getAllAppRoutes(): array
    {
        $routes = app('router')->getRoutes()->getRoutes();

        return array_map(function ($route) {
            return new Route($route);
        }, $routes);
    }
}
