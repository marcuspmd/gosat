<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modality_mappings', function (Blueprint $table) {
            // Remover foreign key antiga se existir
            $table->dropForeign(['institution_id']);
            $table->dropIndex('unique_institution_external_code');

            // Renomear e adicionar campos para rastreabilidade
            $table->renameColumn('standard_modality_code', 'standard_modality_id');
            $table->string('institution_external_id')->nullable()->after('institution_id');
            $table->string('original_modality_name')->nullable()->after('modality_name');
            $table->timestamp('last_seen_at')->nullable()->after('is_active');
            $table->json('metadata')->nullable()->after('last_seen_at');

            // Atualizar foreign keys
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->foreign('standard_modality_id')->references('id')->on('standard_modalities')->onDelete('cascade');

            // Novos índices
            $table->unique(['institution_id', 'external_code', 'institution_external_id'], 'unique_institution_mapping');
            $table->index(['institution_id', 'is_active', 'last_seen_at']);
            $table->index(['standard_modality_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('modality_mappings', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropForeign(['standard_modality_id']);
            $table->dropIndex('unique_institution_mapping');
            $table->dropIndex(['institution_id', 'is_active', 'last_seen_at']);
            $table->dropIndex(['standard_modality_id', 'is_active']);

            $table->renameColumn('standard_modality_id', 'standard_modality_code');
            $table->dropColumn(['institution_external_id', 'original_modality_name', 'last_seen_at', 'metadata']);

            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->unique(['institution_id', 'external_code'], 'unique_institution_external_code');
        });
    }
};
