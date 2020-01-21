<?php


namespace Mtrajano\LaravelSwagger\Http\Controllers;


use DateTime;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use RuntimeException;

class SwaggerAssetController extends Controller
{
    public function index($asset)
    {
        try {
            $path = laravel_swagger_ui_dist_path($asset);

            $contentType = pathinfo($asset)['extension'] == 'css'
                ? 'text/css'
                : 'application/javascript';

            $response = new Response(
                file_get_contents($path), 200, ['Content-Type' => $contentType]
            );

            return $response
                ->setSharedMaxAge(31536000)
                ->setMaxAge(31536000)
                ->setExpires(new DateTime('+1 year'));
        } catch (RuntimeException $exception) {
            return abort(404, $exception->getMessage());
        }
    }
}