<?php

namespace Mtrajano\LaravelSwagger\DataObjects;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model as LaravelModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mtrajano\LaravelSwagger\LaravelSwaggerException;
use phpDocumentor\Reflection;

class Route
{
    /**
     * @var \Illuminate\Routing\Route
     */
    private $_route;

    /**
     * @var \Mtrajano\LaravelSwagger\DataObjects\Middleware[]
     */
    private $_middleware;

    /**
     * @var \phpDocumentor\Reflection\DocBlockFactoryInterface
     */
    private $_docParserFactory;

    public function __construct(LaravelRoute $route, Reflection\DocBlockFactory $docParserFactory = null)
    {
        $this->_route = $route;
        $this->_docParserFactory = $docParserFactory ?? Reflection\DocBlockFactory::createInstance();
        $this->_middleware = $this->_formatMiddleware();
    }

    public function getOriginalUri(): string
    {
        $uri = $this->_route->uri();

        if (!Str::startsWith($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    public function getUri(): string
    {
        return strip_optional_char($this->getOriginalUri());
    }

    /**
     * @return \Mtrajano\LaravelSwagger\DataObjects\Middleware[]
     */
    public function getMiddleware(): array
    {
        return $this->_middleware;
    }

    public function getAction(): string
    {
        return $this->_route->getActionName();
    }

    public function getMethods(): array
    {
        return array_map('strtolower', $this->_route->methods());
    }

    /**
     * Get valid http methods from action.
     */
    public function getActionMethods(): array
    {
        return (array) array_filter($this->getMethods(), function ($route) {
            return $route !== 'head';
        });
    }

    /**
     * Get route name.
     */
    public function getName(): ?string
    {
        return $this->_route->getName();
    }

    /**
     * Get exceptions thrown in action.
     *
     * @throws \ReflectionException
     */
    public function getThrows(): array
    {
        $docBlock = $this->_getActionDocBlock();

        $exceptions = $this->_getTagValuesForDocblock($docBlock, 'throws');

        return array_unique(array_map(function ($e) {
            return trim(trim($e), '\\');
        }, $exceptions));
    }

    /**
     * Get model searching on route.
     *
     * @throws \ReflectionException
     */
    public function getModel(): ?LaravelModel
    {
        $modelName = $this->_getModelNameFromMethodDocs()
            ?? $this->_getModelNameFromControllerDocs();

        if (!$modelName) {
            return null;
        }

        if (!is_subclass_of($modelName, LaravelModel::class)) {
            throw new LaravelSwaggerException(
                "{$modelName} @model must be an instance of [" . LaravelModel::class . ']'
            );
        }

        return new $modelName;
    }

    /**
     * @return \Mtrajano\LaravelSwagger\DataObjects\Middleware[]
     */
    private function _formatMiddleware(): array
    {
        return array_map(function ($middleware) {
            return new Middleware($middleware);
        }, $this->_route->gatherMiddleware());
    }

    /**
     * Get annotation from specific model. You can pass the docBlock
     * content on param $docBlock. By default will be used the docBlock
     * from action.
     *
     * @throws \ReflectionException
     */
    private function _getModelNameFromMethodDocs(?string $docBlock = null): ?string
    {
        $docBlock = $docBlock ?? $this->_getActionDocBlock();

        return $this->_getTagValuesForDocblock($docBlock, 'model')[0] ?? null;
    }

    /**
     * Get model name from Controller DocBlock.
     *
     * @return string|null
     *
     * @throws \ReflectionException
     */
    private function _getModelNameFromControllerDocs(): ?string
    {
        [$class] = Str::parseCallback($this->getAction());

        if (!$class) {
            return null;
        }

        $reflection = new \ReflectionClass($class);

        $docBlock = $reflection->getDocComment();

        return $this->_getModelNameFromMethodDocs($docBlock);
    }

    /**
     * Get action DockBlock.
     *
     * @throws \ReflectionException
     */
    private function _getActionDocBlock(): string
    {
        $actionInstance = $this->_getActionClassInstance();

        return $actionInstance ? $actionInstance->getDocComment() ?: '' : '';
    }

    /**
     * Return a ReflectionMethod instance from current action.
     *
     * @throws \ReflectionException
     */
    private function _getActionClassInstance(): ?\ReflectionMethod
    {
        [$class, $method] = Str::parseCallback($this->getAction());

        if (!$class || !$method) {
            return null;
        }

        return new \ReflectionMethod($class, $method);
    }

    private function _getTagValuesForDocblock(string $docBlock, string $tag): array
    {
        if (!$docBlock || !$tag) {
            return [];
        }

        $docBlock = $this->_docParserFactory->create($docBlock);

        $values = array_map(function ($tag) {
            return (string) $tag;
        }, $docBlock->getTagsByName($tag));

        return array_values(array_filter($values));
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
        $actionInstance = $this->_getActionClassInstance();
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
     * Check if this route has auth middleware.
     */
    public function hasAuthMiddleware(): bool
    {
        foreach ($this->getMiddleware() as $middleware) {
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
