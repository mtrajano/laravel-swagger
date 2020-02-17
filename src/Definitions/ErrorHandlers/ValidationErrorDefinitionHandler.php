<?php

namespace Mtrajano\LaravelSwagger\Definitions\ErrorHandlers;

use Illuminate\Validation\ValidationException;
use ReflectionException;

class ValidationErrorDefinitionHandler extends DefaultDefinitionHandler
{
    /**
     * @return array
     * @throws ReflectionException
     */
    public function getDefinitionContent(): array
    {
        $formRequest = $this->getRoute()->getFormRequestFromParams();
        $errorsProperties = [];
        if ($formRequest) {
            $exceptions[] = ValidationException::class;
            foreach ($formRequest->rules() as $property => $rules) {
                $errorsProperties[$property] = [
                    'type' => 'array',
                    'description' => "Errors on \"{$property}\" parameter",
                    'items' => [
                        'type' => 'string',
                    ],
                ];
            }
        }

        return [
            'type' => 'object',
            'required' => [
                'message',
                'errors',
            ],
            'properties' => [
                'message' => [
                    'type' => 'string',
                    'example' => 'The given data was invalid',
                ],
                'errors' => [
                    'type' => 'object',
                    'properties' => $errorsProperties
                ],
            ],
        ];
    }
}