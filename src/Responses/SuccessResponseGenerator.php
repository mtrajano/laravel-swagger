<?php

namespace Mtrajano\LaravelSwagger\Responses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use ReflectionException;

class SuccessResponseGenerator
{
    /**
     * @var Route
     */
    private $route;
    /**
     * @var Model
     */
    private $model;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * @throws ReflectionException
     */
    public function generate(): array
    {
        $methodMappingHttpCode = [
            'get' => 200,
            'post' => 201,
            'put' => 204,
            'delete' => 204,
        ];

        // Get the status code from route method
        $methods = $this->route->validMethods();

        // TODO: Handle with many methods in same route. E.g.: Route::match(['GET', 'POST']);
        $httpCode = $methodMappingHttpCode[$methods[0]];

        $description = $this->getDescriptionByHttpCode($httpCode);

        $this->setModelFromRouteAction();
        if (!$this->model) {
            return [];
        }

        $response = [
            $httpCode => [
                'description' => $description,
            ],
        ];

        $schema = $this->mountSchema($httpCode);
        if (!empty($schema)) {
            $response[$httpCode]['schema'] = $schema;
        }

        return $response;
    }

    private function getDescriptionByHttpCode(int $httpCode): string
    {
        $httpCodeDescription = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
        ];

        return $httpCodeDescription[$httpCode] ?? '';
    }

    private function getDefinitionName(): string
    {
        return class_basename($this->model);
    }

    private function mountSchema(int $httpCode): array
    {
        if ($httpCode === 204) {
            return [];
        }

        $schema = ['$ref' => '#/definitions/' . $this->getDefinitionName()];

        if ($this->isTypeArrayRoute()) {
            $schema = [
                'type' => 'array',
                'items' => $schema,
            ];
        }

        return $schema;
    }

    private function isTypeArrayRoute(): bool
    {
        // Only check it. To check if the route parameters is empty can be wrong
        // for routes like "/orders/{id}/products".
        return Str::contains($this->route->getName(), 'index');
    }

    /**
     * @throws ReflectionException
     */
    private function setModelFromRouteAction(): void
    {
        $this->model = $this->route->getModel();
    }
}
