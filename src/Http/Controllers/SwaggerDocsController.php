<?php

namespace Mtrajano\LaravelSwagger\Http\Controllers;

use Illuminate\Routing\Controller;

class SwaggerDocsController extends Controller
{
    /**
     * Return the page with swagger docs.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $filePath = config('app.url').'/'.config('laravel-swagger.file_path');

        return view('laravel-swagger::index')->with('filePath', $filePath);
    }
}