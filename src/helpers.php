<?php

if (!function_exists('strip_optional_char')) {
    function strip_optional_char($uri)
    {
        return str_replace('?', '', $uri);
    }
}
