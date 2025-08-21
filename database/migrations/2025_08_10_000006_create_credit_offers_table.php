<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('credit_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('institution_id');
            $table->uuid('modality_id');
            $table->bigInteger('min_amount_cents')->unsigned();
            $table->bigInteger('max_amount_cents')->unsigned();
            $table->integer('min_installments')->unsigned();
            $table->integer('max_installments')->unsigned();
            $table->decimal('monthly_interest_rate', 8, 6)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->foreign('modality_id')->references('id')->on('credit_modalities')->onDelete('cascade');

            $table->index(['customer_id'], 'idx_credit_offers_customer');
            $table->index(['created_at'], 'idx_credit_offers_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_offers');
    }
};
