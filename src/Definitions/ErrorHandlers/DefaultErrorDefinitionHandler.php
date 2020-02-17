<?php

namespace Mtrajano\LaravelSwagger\Definitions\ErrorHandlers;

class DefaultErrorDefinitionHandler extends DefaultDefinitionHandler
{
    public function getDefinitionContent(): array
    {
        return [
            'type' => 'object',
            'required' => [
                'message',
            ],
            'properties' => [
                'message' => [
                    'type' => 'string',
                    'example' => 'The given data was invalid',
                ],
            ],
        ];
    }
}