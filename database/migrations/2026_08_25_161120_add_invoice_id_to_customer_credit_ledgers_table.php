<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_ledgers', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->after('discount_request_id')
                ->index()
                ->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_ledgers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
