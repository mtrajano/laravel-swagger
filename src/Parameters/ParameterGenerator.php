<?php

namespace Mtrajano\LaravelSwagger\Parameters;

interface ParameterGenerator
{
    public function getParameters(): array;

    public function getParamLocation(): string;
}
