<?php

namespace Mtrajano\LaravelSwagger\DataObjects;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mtrajano\LaravelSwagger\LaravelSwaggerException;
use ReflectionClass;
use ReflectionMethod;

class Route
{
    private $route;
    /**
     * @var Middleware[]
     */
    private $middleware;

    public function __construct(LaravelRoute $route)
    {
        $this->route = $route;
        $this->middleware = $this->formatMiddleware();
    }

    public function originalUri()
    {
        $uri = $this->route->uri();

        if (!Str::startsWith($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    public function uri()
    {
        return strip_optional_char($this->originalUri());
    }

    /**
     * @return array|Middleware[]
     */
    public function middleware()
    {
        return $this->middleware;
    }

    public function action(): string
    {
        return $this->route->getActionName();
    }

    public function methods()
    {
        return array_map('strtolower', $this->route->methods());
    }

    /**
     * Get valid http methods from action.
     *
     * @return array
     */
    public function validMethods(): array
    {
        return (array) array_filter($this->methods(), function ($route) {
            return $route !== 'head';
        });
    }

    protected function formatMiddleware()
    {
        return array_map(function ($middleware) {
            return new Middleware($middleware);
        }, $this->route->gatherMiddleware());
    }

    /**
     * Get route name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->route->getName();
    }

    /**
     * Get model name from Controller DocBlock.
     *
     * @return string|null
     *
     * @throws \ReflectionException
     */
    private function getModelNameFromControllerDocs(): ?string
    {
        [$class] = Str::parseCallback($this->action());

        if (!$class) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        $docBlock = $reflection->getDocComment();

        return $this->getModelNameFromMethodDocs($docBlock);
    }

    /**
     * Get model searching on route.
     *
     * @throws \ReflectionException
     */
    public function getModel(): ?Model
    {
        $modelName = $this->getModelNameFromMethodDocs()
            ?? $this->getModelNameFromControllerDocs();

        if (!$modelName) {
            return null;
        }

        if (!is_subclass_of($modelName, Model::class)) {
            throw new LaravelSwaggerException(
                "{$modelName} @model must be an instance of [" . Model::class . ']'
            );
        }

        return new $modelName;
    }

    /**
     * Get action DockBlock.
     *
     * @return string
     *
     * @throws \ReflectionException
     */
    private function getActionDocBlock()
    {
        $actionInstance = $this->getActionClassInstance();

        return $actionInstance ? $actionInstance->getDocComment() ?: '' : '';
    }

    /**
     * Return a ReflectionMethod instance from current action.
     *
     * @throws \ReflectionException
     */
    private function getActionClassInstance(): ?ReflectionMethod
    {
        [$class, $method] = Str::parseCallback($this->action());

        if (!$class || !$method) {
            return null;
        }

        return new ReflectionMethod($class, $method);
    }

    /**
     * Check the action has a FormRequest on params.
     *
     * @throws \ReflectionException
     */
    public function hasFormRequestOnParams(): bool
    {
        return (bool) $this->getFormRequestClassFromParams();
    }

    /**
     * @throws \ReflectionException
     */
    public function getFormRequestFromParams(): ?FormRequest
    {
        $class = $this->getFormRequestClassFromParams();
        if (!$class) {
            return null;
        }

        return new $class();
    }

    /**
     * @throws \ReflectionException
     */
    public function getFormRequestClassFromParams(): ?string
    {
        $actionInstance = $this->getActionClassInstance();
        if (!$actionInstance) {
            return null;
        }

        $parameters = $actionInstance->getParameters();

        foreach ($parameters as $parameter) {
            $reflectionClass = $parameter->getClass();
            if (!$reflectionClass) {
                continue;
            }

            $class = $reflectionClass->getName();

            if (is_subclass_of($class, FormRequest::class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Get exceptions thrown in action.
     *
     * @throws \ReflectionException
     */
    public function getThrows(): array
    {
        $docBlock = $this->getActionDocBlock();

        $exceptions = get_annotations($docBlock, '@throws');

        if (!empty($exceptions)) {
            $exceptions = array_unique(array_map(function ($e) {
                $trimmed_exception = preg_replace('/^\s+|\s+$/', '', $e);

                return trim($trimmed_exception, '\\');
            }, $exceptions));
        }

        return $exceptions;
    }

    /**
     * Get annotation from specific model. You can pass the docBlock
     * content on param $docBlock. By default will be used the docBlock
     * from action.
     *
     * @throws \ReflectionException
     */
    private function getModelNameFromMethodDocs(?string $docBlock = null): ?string
    {
        $docBlock = $docBlock ?? $this->getActionDocBlock();

        return get_annotations($docBlock, '@model')[0] ?? null;
    }

    /**
     * Check if this route has auth middleware.
     *
     * @return bool
     */
    public function hasAuthMiddleware(): bool
    {
        foreach ($this->middleware() as $middleware) {
            if (Str::contains($middleware->name(), 'auth')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get exceptions that can generated by the route action. Get it from doc
     * block, form request if exists and auth middleware if defined.
     *
     * @throws \ReflectionException
     */
    public function getExceptions(): array
    {
        $exceptions = $this->getThrows();

        if ($this->hasFormRequestOnParams()) {
            $exceptions[] = ValidationException::class;
        }

        $hasAuthMiddleware = $this->hasAuthMiddleware();
        if ($hasAuthMiddleware) {
            $exceptions[] = AuthenticationException::class;
        }

        return array_unique($exceptions);
    }
}
