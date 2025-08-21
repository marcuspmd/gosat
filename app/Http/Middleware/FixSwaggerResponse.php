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

        // Apenas para a rota nomeada 'api.docs.json', se conteúdo JSON estiver presente,
        // forçar status 200 para compatibilidade com Swagger UI
        if ($request->route()?->getName() === 'api.docs.json' &&
            $response->headers->get('content-type') === 'application/json' &&
            ! empty($response->getContent())) {

            $response->setStatusCode(200);
        }

        return $response;
    }
}
