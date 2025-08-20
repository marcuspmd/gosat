<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_id');
            $table->string('cpf', 11);
            $table->uuid('institution_id');
            $table->uuid('modality_id');
            $table->bigInteger('min_amount_cents')->unsigned();
            $table->bigInteger('max_amount_cents')->unsigned();
            $table->bigInteger('approved_amount_cents')->unsigned();
            $table->decimal('monthly_interest_rate', 8, 6);
            $table->integer('min_installments')->unsigned();
            $table->integer('max_installments')->unsigned();
            $table->integer('installments')->unsigned();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'expired'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->foreign('modality_id')->references('id')->on('credit_modalities')->onDelete('cascade');

            $table->index(['cpf', 'request_id'], 'idx_credit_offers_cpf_request');
            $table->index(['request_id'], 'idx_credit_offers_request');
            $table->index(['cpf', 'status'], 'idx_credit_offers_cpf_status');
            $table->index(['status'], 'idx_credit_offers_status');
            $table->index(['created_at'], 'idx_credit_offers_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_offers');
    }
};
