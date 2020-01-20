<?php

namespace Mtrajano\LaravelSwagger\Definitions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mtrajano\LaravelSwagger\DataObjects\Route;
use ReflectionException;
use RuntimeException;

class DefinitionGenerator
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var Model
     */
    private $model;
    /**
     * @var array
     */
    private $definitions = [];

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
        if (!$this->canGenerate()) {
            return [];
        }

        $this->setModelFromRouteAction();
        if ($this->model === false) {
            return [];
        }

        $this->generateFromCurrentModel();

        $this->generateFromRelations();

        return array_reverse($this->definitions);
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
     * @throws ReflectionException
     */
    private function setModelFromRouteAction()
    {
        $this->model = $this->route->getModel();
    }

    private function canGenerate()
    {
        return $this->allowsHttpMethodGenerate();
    }

    private function allowsHttpMethodGenerate(): bool
    {
        $allowGenerateDefinitionMethods = ['get', 'post'];

        $methods = array_filter($this->route->methods(), function ($route) {
            return $route !== 'head';
        });

        foreach ($methods as $method) {
            if (!in_array($method, $allowGenerateDefinitionMethods)) {
                return false;
            }
        }
        return true;
    }

    private function getModelColumns()
    {
        return Schema::getColumnListing($this->model->getTable());
    }

    private function getDefinitionProperties()
    {
        $columns = $this->getModelColumns();

        $hiddenColumns = $this->model->getHidden();

        if (method_exists($this->model, 'getAppends')) {
            $appends = $this->model->getAppends();
            // TODO: Test condition
            if (!is_array($appends)) {
                throw new RuntimeException(
                    'The return type of the "getAppends" method must be an array.'
                );
            }

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
     * WARNING: Disabled until solve database connection problem to don't
     *          create data on production database.
     *
     * @return Model|null
     * @todo Check problem creating registries on production database:
     *       - Change the connection?
     *       - Abort?
     */
    private function getModelFake(): ?Model
    {
        return null;

        /*try {
            return factory(get_class($this->model))->create();
        } catch (InvalidArgumentException $e) {
            return null;
        }*/
    }

    /**
     * Identify all relationships for a given model
     *
     * @param Model $model Model
     * @param string $heritage A flag that indicates whether parent and/or child relationships should be included
     * @return  array
     * @throws ReflectionException
     */
    public function getAllRelations(Model $model = null, $heritage = 'all')
    {
        return get_all_model_relations($model, $heritage);
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
     * @param Model $model
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

        $defaultDefinition = [
            'type' => 'string'
        ];

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
     *
     * @param string $definition
     * @param string $property
     * @param $data
     */
    private function setPropertyOnDefinition(
        string $definition,
        string $property,
        $data
    ) {
        $this->definitions[$definition]['properties'][$property] = $data;
    }

    /**
     * Generate the definition from model Relations.
     *
     * @throws ReflectionException
     */
    private function generateFromRelations()
    {
        $relations = $this->getAllRelations($this->model);

        $baseModel = $this->model;
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
}