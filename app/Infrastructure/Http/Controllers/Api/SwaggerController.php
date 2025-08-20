<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use OpenApi\Generator;

class SwaggerController extends Controller
{
    /**
     * Serve the swagger UI interface.
     */
    public function docs(): Response
    {
        return response()->view('swagger.docs', [
            'apiDocsUrl' => route('api.docs.json'),
        ]);
    }

    /**
     * Generate and return the OpenAPI JSON specification.
     */
    public function json(): JsonResponse
    {
        try {
            $openapi = Generator::scan([
                app_path('Infrastructure/Http/Controllers/Api'),
                app_path('Infrastructure/Http/Resources'),
                app_path('Infrastructure/Http/Swagger'),
            ], [
                'exclude' => [
                    app_path('Console'),
                    app_path('Exceptions'),
                    app_path('Providers'),
                    app_path('Infrastructure/Persistence'),
                    base_path('tests'),
                    base_path('database'),
                    base_path('storage'),
                    base_path('vendor'),
                ],
            ]);

            return response()->json(json_decode($openapi->toJson(), true), 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate OpenAPI specification',
                'message' => $e->getMessage(),
                'debug' => app()->isLocal() ? $e->getTraceAsString() : null,
            ], 500)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    }

    /**
     * Generate OpenAPI docs and save to storage.
     */
    public function generate(): JsonResponse
    {
        $openapi = Generator::scan([
            app_path('Infrastructure/Http/Controllers/Api'),
            app_path('Infrastructure/Http/Resources'),
            app_path('Infrastructure/Http/Swagger'),
        ], [
            'exclude' => [
                app_path('Console'),
                app_path('Exceptions'),
                app_path('Providers'),
                app_path('Infrastructure/Persistence'),
                base_path('tests'),
                base_path('database'),
                base_path('storage'),
                base_path('vendor'),
            ],
        ]);

        $docsPath = config('swagger.documentations.default.paths.docs');
        if (! is_dir($docsPath)) {
            mkdir($docsPath, 0755, true);
        }

        $jsonPath = $docsPath . '/api-docs.json';
        file_put_contents($jsonPath, $openapi->toJson());

        return response()->json([
            'message' => 'API documentation generated successfully',
            'path' => $jsonPath,
            'size' => filesize($jsonPath) . ' bytes',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
