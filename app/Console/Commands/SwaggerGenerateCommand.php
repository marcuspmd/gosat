<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;

class SwaggerGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swagger:generate
                           {--output= : Output directory for the generated docs}
                           {--format=json : Output format (json or yaml)}
                           {--exclude= : Additional paths to exclude}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate OpenAPI (Swagger) documentation from code annotations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Generating OpenAPI documentation...');

        $outputDir = $this->option('output') ?? storage_path('api-docs');
        $format = $this->option('format') ?? 'json';
        $additionalExcludes = $this->option('exclude') ? explode(',', $this->option('exclude')) : [];

        // Ensure output directory exists
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
            $this->info("📁 Created output directory: $outputDir");
        }

        // Default paths to scan (focused on API controllers, resources, and swagger definitions)
        $scanPaths = [
            app_path('Infrastructure/Http/Controllers/Api'),
            app_path('Infrastructure/Http/Resources'),
            app_path('Infrastructure/Http/Swagger'),
        ];

        // Default exclusions
        $excludePaths = [
            app_path('Console'),
            app_path('Exceptions'),
            app_path('Providers'),
            app_path('Infrastructure/Persistence'),
            base_path('tests'),
            base_path('database'),
            base_path('storage'),
            base_path('vendor'),
            ...array_map('realpath', $additionalExcludes),
        ];

        try {
            // Generate OpenAPI specification
            $openapi = new Generator()->generate($scanPaths);

            // Determine output filename
            $filename = match ($format) {
                'yaml', 'yml' => 'api-docs.yaml',
                default => 'api-docs.json',
            };

            $outputPath = $outputDir . DIRECTORY_SEPARATOR . $filename;

            // Save the documentation
            $content = match ($format) {
                'yaml', 'yml' => $openapi->toYaml(),
                default => $openapi->toJson(),
            };

            file_put_contents($outputPath, $content);

            $this->info('✅ OpenAPI documentation generated successfully!');
            $this->info("📄 Output file: $outputPath");
            $this->info('📦 File size: ' . $this->formatBytes(filesize($outputPath)));

            // Count endpoints
            $spec = json_decode($openapi->toJson(), true);
            $endpointCount = 0;
            if (isset($spec['paths'])) {
                foreach ($spec['paths'] as $methods) {
                    $endpointCount += count($methods);
                }
            }
            $this->info("🔗 Total endpoints documented: $endpointCount");

            // Show usage instructions
            $this->newLine();
            $this->info('🌐 To view the documentation:');
            $this->info('   • Swagger UI: http://localhost:8080/api/docs');
            $this->info('   • JSON spec: http://localhost:8080/api/docs.json');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to generate documentation: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < 4; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
