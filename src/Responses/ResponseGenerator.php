<?php

namespace Mtrajano\LaravelSwagger\Responses;

class ResponseGenerator implements ResponseGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function getResponses($uri, $method, $actionInstance)
    {
        return [
            '200' => [
                'description' => 'OK',
            ],
        ];
    }
}
