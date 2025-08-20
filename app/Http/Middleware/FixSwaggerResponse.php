<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixSwaggerResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // If this is a Swagger docs request and contains JSON content,
        // force the status to 200
        if (str_contains($request->path(), 'docs') &&
            $response->headers->get('content-type') === 'application/json' &&
            ! empty($response->getContent())) {

            $response->setStatusCode(200);
        }

        return $response;
    }
}
