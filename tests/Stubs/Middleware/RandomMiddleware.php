<?php

namespace Mtrajano\LaravelSwagger\Tests\Stubs\Middleware;

class RandomMiddleware
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
