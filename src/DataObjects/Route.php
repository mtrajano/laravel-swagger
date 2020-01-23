<?php

namespace Mtrajano\LaravelSwagger\DataObjects;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;

class Route
{
    private $route;
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

    public function middleware()
    {
        return $this->middleware;
    }

    public function action()
    {
        return $this->route->getAction()['uses'];
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
    public function validMethods()
    {
        return array_filter($this->methods(), function ($route) {
            return $route !== 'head';
        });
    }

    protected function formatMiddleware()
    {
        $middleware = $this->route->getAction()['middleware'] ?? [];

        return array_map(function ($middleware) {
            return new Middleware($middleware);
        }, Arr::wrap($middleware));
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
     * @throws ReflectionException
     */
    private function getModelNameFromControllerDocs(): ?string
    {
        $action = $this->action();

        list($class) = is_string($action) ? Str::parseCallback($action) : [null];

        if (!$class) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        $docBlock = $reflection->getDocComment();

        $annotations = $this->getModelAnnotations($docBlock);
        if (empty($annotations)) {
            return null;
        }

        return $annotations[0];
    }

    /**
     * Get model searching on route.
     *
     * @throws ReflectionException|RuntimeException
     * @return Model|false
     */
    public function getModel()
    {
        $modelName = $this->getModelNameFromMethodDocs()
            ?? $this->getModelNameFromControllerDocs();

        if (!$modelName) {
            return false;
        }

        if (!is_subclass_of($modelName, Model::class)) {
            throw new RuntimeException(
                'The @model must be an instance of ['.Model::class.']'
            );
        }

        return new $modelName;
    }

    /**
     * Get action DockBlock.
     *
     * @return string
     * @throws ReflectionException
     */
    private function getActionDocBlock()
    {
        $action = $this->action();

        $actionInstance = is_string($action) ? $this->getActionClassInstance($action) : null;

        return $actionInstance ? ($actionInstance->getDocComment() ?: '') : '';
    }

    /**
     * Get Model name from method DocBlock.
     *
     * @return string|null
     * @throws ReflectionException
     */
    private function getModelNameFromMethodDocs(): ?string
    {
        $docBlock = $this->getActionDocBlock();

        $annotations = $this->getModelAnnotations($docBlock);

        if (!$annotations) {
            return null;
        }

        return reset($annotations);
    }

    /**
     * Return a ReflectionMethod instance from current action.
     *
     * @param string $action
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    private function getActionClassInstance(string $action)
    {
        list($class, $method) = Str::parseCallback($action);

        return new ReflectionMethod($class, $method);
    }

    /**
     * Check the action has a FormRequest on params.
     *
     * @return bool
     * @throws ReflectionException
     */
    public function hasFormRequestOnParams()
    {
        return (bool) $this->getFormRequestClassFromParams();
    }

    /**
     * @return FormRequest|null
     * @throws ReflectionException
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
     * @return string|null
     * @throws ReflectionException
     */
    public function getFormRequestClassFromParams()
    {
        if (!is_string($this->action())) {
            return null;
        }

        $parameters = $this->getActionClassInstance($this->action())->getParameters();

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
     * @return array
     * @throws ReflectionException
     */
    public function getThrows(): array
    {
        $docBlock = $this->getActionDocBlock();

        return get_annotations($docBlock, '@throws');
    }

    /**
     * Get annotations from specific model.
     *
     * @param string $docBlock
     * @return array
     */
    private function getModelAnnotations(string $docBlock): array
    {
        return get_annotations($docBlock, '@model');
    }
}
