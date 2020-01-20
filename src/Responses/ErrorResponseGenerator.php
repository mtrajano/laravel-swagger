<?php

namespace Mtrajano\LaravelSwagger\Responses;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use ReflectionException;

class ErrorResponseGenerator
{
    /**
     * @var Route
     */
    private $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function generate()
    {
        $response = [];

        // Check if exists FormValidator in action: 422
        if ($this->route->hasFormRequestOnParams()) {
            $response['422'] = [
                'description' => 'Validation errors',
            ];
        }

        // Check throws from DockBlock: 404, 403
        $exceptions = $this->route->getThrows();

        $exceptionsResponse = [
            AuthenticationException::class => [
                '401' => [
                    'description' => 'Unauthenticated',
                ],
            ],
            ModelNotFoundException::class => [
                '404' => [
                    'description' => 'Model not found',
                ],
            ],
            AuthorizationException::class => [
                '403' => [
                    'description' => 'Forbidden',
                ],
            ],
        ];

        foreach ($exceptions as $exception) {
            $responseDefinition = $exceptionsResponse[trim($exception, "\ \t\n\r\0\x0B")] ?? null;
            if ($responseDefinition) {
                $response += $responseDefinition;
            }
        }

        // Check if has auth middleware: 401
        $hasAuthMiddleware = $this->hasAuthMiddleware();
        if ($hasAuthMiddleware) {
            $response['401'] = [
                'description' => 'Unauthenticated',
            ];
        }

        return $response;
    }

    private function hasAuthMiddleware()
    {
        foreach ($this->route->middleware() as $middleware) {
            if (Str::contains($middleware->name(), 'auth')) {
                return true;
            }
        }

        return false;
    }
}