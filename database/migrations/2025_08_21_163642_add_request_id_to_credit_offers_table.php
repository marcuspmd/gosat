<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('credit_offers', function (Blueprint $table) {
            $table->uuid('request_id')->nullable()->after('id');
            $table->index(['request_id'], 'idx_credit_offers_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_offers', function (Blueprint $table) {
            $table->dropIndex('idx_credit_offers_request');
            $table->dropColumn('request_id');
        });
    }
};
