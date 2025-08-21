<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('credit_modalities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('standard_code');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['standard_code']);
            $table->index(['is_active', 'standard_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_modalities');
    }
};
