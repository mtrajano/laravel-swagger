<?php

namespace Mtrajano\LaravelSwagger;

interface GeneratorContract
{
    public function generate();

    public function setRouteFilter($routeFilter);
}
