<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modality_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institution_id');
            $table->string('external_code');
            $table->string('standard_modality_code');
            $table->string('modality_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->unique(['institution_id', 'external_code'], 'unique_institution_external_code');
            $table->index(['institution_id', 'is_active']);
            $table->index(['standard_modality_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modality_mappings');
    }
};
