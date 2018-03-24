<?php

namespace Mtrajano\LaravelSwagger\Formatters;

class JsonFormatter extends Formatter
{
    public function format()
    {
        if (!extension_loaded('json')) {
            throw new \Exception('JSON extension must be loaded to use the json output format');
        }

        return json_encode($this->docs, JSON_PRETTY_PRINT);
    }
}