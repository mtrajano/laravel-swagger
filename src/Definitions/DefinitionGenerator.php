<?php

namespace Mtrajano\LaravelSwagger\Definitions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use Mtrajano\LaravelSwagger\Definitions\ErrorHandlers\DefaultDefinitionHandler;

class DefinitionGenerator
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var Model|null
     */
    private $model;

    /**
     * @var array
     */
    private $definitions = [];

    /**
     * @var array
     */
    private $errorsDefinitions;

    public function __construct(Route $route, array $errorsDefinitions)
    {
        $this->route = $route;
        $this->errorsDefinitions = $errorsDefinitions;
    }

    /**
     * @throws \ReflectionException
     */
    public function generate(): array
    {
        if ($this->allowsHttpMethodGenerate()) {
            $this->setModelFromRouteAction();
            if ($this->model) {
                $this->generateFromCurrentModel();

                $this->generateFromRelations();
            }
        }

        $this->generateFromErrors();

        return array_reverse($this->definitions, true);
    }

    private function getPropertyDefinition($column)
    {
        $definition = $this->mountColumnDefinition($column);

        $modelFake = $this->getModelFake();
        // TODO: Create tests to case when has no factory defined to model.
        if ($modelFake) {
            $definition['example'] = (string) $modelFake->{$column};
        }

        return $definition;
    }

    /**
     * Get model searching on route and define the found model on $this->model
     * property.
     *
     * @throws \ReflectionException
     */
    private function setModelFromRouteAction(): void
    {
        $this->model = $this->route->getModel();
    }

    /**
     * Check if all http methods from route allows generate definitions.
     */
    private function allowsHttpMethodGenerate(): bool
    {
        $allowGenerateDefinitionMethods = ['get', 'post'];

        foreach ($this->route->validMethods() as $method) {
            if (!in_array($method, $allowGenerateDefinitionMethods)) {
                return false;
            }
        }
        return true;
    }

    private function getModelColumns(): array
    {
        return Schema::getColumnListing($this->model->getTable());
    }

    private function getDefinitionProperties()
    {
        $columns = $this->getModelColumns();

        $hiddenColumns = $this->model->getHidden();

        if (method_exists($this->model, 'getAppends')) {
            $columns = array_merge($columns, $this->model->getAppends());
        }

        $properties = [];
        foreach ($columns as $column) {
            if (in_array($column, $hiddenColumns)) {
                continue;
            }

            $properties[$column] = $this->getPropertyDefinition($column);
        }

        return $properties;
    }

    private function getDefinitionName(): string
    {
        return class_basename($this->model);
    }

    /**
     * Create an instance of the model with fake data or return null.
     */
    private function getModelFake(): ?Model
    {
        try {
            return factory(get_class($this->model))->make();
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Identify all relationships for a given model
     *
     * @throws \ReflectionException
     */
    public function getAllRelations(Model $model): array
    {
        return get_all_model_relations($model);
    }

    private function generateFromCurrentModel()
    {
        if ($this->definitionExists()) {
            return false;
        }

        $this->definitions += [
            $this->getDefinitionName() => [
                'type' => 'object',
                'properties' => $this->getDefinitionProperties(),
            ],
        ];
        return true;
    }

    /**
     * @return $this
     */
    private function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }

    private function mountColumnDefinition(string $column)
    {
        $casts = $this->model->getCasts();
        $datesFields = $this->model->getDates();

        if (in_array($column, $datesFields)) {
            return [
                'type' => 'string',
                'format' => 'date-time',
            ];
        }

        $defaultDefinition = ['type' => 'string'];

        $laravelTypesSwaggerTypesMapping = [
            'float' => [
                'type' => 'number',
                'format' => 'float',
            ],
            'int' => [
                'type' => 'integer',
            ],
            'boolean' => [
                'type' => 'boolean',
            ],
            'string' => $defaultDefinition,
        ];

        $columnType = $this->model->hasCast($column) ? $casts[$column] : 'string';

        return $laravelTypesSwaggerTypesMapping[$columnType] ?? $defaultDefinition;
    }

    /**
     * Set property data on specific definition.
     */
    private function setPropertyOnDefinition(string $definition, string $property, $data)
    {
        $this->definitions[$definition]['properties'][$property] = $data;
    }

    /**
     * Generate the definition from model Relations.
     *
     * @throws \ReflectionException
     */
    private function generateFromRelations()
    {
        $baseModel = $this->model;
        $relations = $this->getAllRelations($this->model);

        foreach ($relations as $relation) {
            $this->setModel($baseModel);

            $relatedModel = $relation['related_model'];

            $relationPropertyData = [
                '$ref' => '#/definitions/'.class_basename($relatedModel)
            ];
            if (Str::contains($relation['relation'], 'Many')) {
                $relationPropertyData = [
                    'type' => 'array',
                    'items' => $relationPropertyData,
                ];
            }

            $this->setPropertyOnDefinition(
                $this->getDefinitionName(),
                $relation['method'],
                $relationPropertyData
            );

            $generated = $this
                ->setModel($relatedModel)
                ->generateFromCurrentModel();

            if ($generated === false) {
                continue;
            }

            $this->generateFromRelations();
        }
    }

    /**
     * Check if definition exists.
     *
     * @return bool
     */
    private function definitionExists()
    {
        return isset($this->definitions[$this->getDefinitionName()]);
    }

    /**
     * Generate definition from errors.
     *
     * @throws \ReflectionException
     */
    private function generateFromErrors()
    {
        $exceptions = $this->route->getExceptions();

        foreach ($exceptions as $exception) {
            $definitionHandler = $this->findExceptionDefinitionHandler($exception);
            if (!$definitionHandler) {
                continue;
            }

            $this->definitions += $definitionHandler->handle();
        }
    }

    /**
     * @param $exception
     * @return DefaultDefinitionHandler|null
     * @throws \ReflectionException
     */
    private function findExceptionDefinitionHandler($exception): ?DefaultDefinitionHandler
    {
        $formRequestClass = $this->route->getFormRequestClassFromParams();
        $formRequestRef = $formRequestClass
            ? class_basename($formRequestClass)
            : '';

        foreach ($this->errorsDefinitions as $ref => $errorDefinition) {
            if ($errorDefinition['exception'] === $exception) {
                $ref = $errorDefinition['http_code'] == 422 && $formRequestClass
                    ? $formRequestRef
                    : (string) $ref;

                return new $errorDefinition['handler']($this->route, $ref);
            }
        }

        return null;
    }
}