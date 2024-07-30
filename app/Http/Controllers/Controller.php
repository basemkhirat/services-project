<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *    title="Services Project",
 *    version="1.0.0",
 * )
 */
abstract class Controller
{
    /**
     * generate a a unique cache key per url
     */
    protected function getRequestKey($request)
    {
        $url = $request->url();

        $queryParams = $request->query();

        ksort($queryParams);

        $queryString = http_build_query($queryParams);

        $fullUrl = strtolower("{$url}?{$queryString}");

        return sha1($fullUrl);
    }
}
