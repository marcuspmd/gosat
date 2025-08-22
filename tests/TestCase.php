<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Vite;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Vite for tests to avoid manifest not found errors
        Vite::macro('useBuildDirectory', function () {
            return $this;
        });
        
        // Override Vite to return empty strings during tests
        $this->app->bind('Illuminate\Foundation\Vite', function () {
            return new class {
                public function __invoke($_entrypoints = [], $_buildDirectory = null) {
                    return '';
                }
                
                public function __toString() {
                    return '';
                }
                
                public function useBuildDirectory($_buildDirectory) {
                    return $this;
                }
                
                public function withEntryPoints($_entryPoints) {
                    return $this;
                }
            };
        });
    }
}
