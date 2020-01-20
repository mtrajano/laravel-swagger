<?php

namespace Mtrajano\LaravelSwagger\Responses;

use Mtrajano\LaravelSwagger\DataObjects\Route;

class ResponseGenerator
{
    /**
     * @var Route
     */
    private $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function generate()
    {
        $generators = $this->getGenerators();

        $response = [];
        foreach ($generators as $generator) {
            $response += $generator->generate();
        }

        if (empty($response)) {
            // Set default response
            $response = [
                '200' => [
                    'description' => 'OK',
                ],
            ];
        }

        return $response;
    }

    /**
     * @return array
     */
    private function getGenerators()
    {
        return [
            new SuccessResponseGenerator($this->route),
            new ErrorResponseGenerator($this->route),
        ];
    }
}