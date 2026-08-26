<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->index()->constrained();
            $table->foreignId('credit_ledger_id')->nullable()->index()->constrained('customer_credit_ledgers');
            $table->foreignId('discount_request_id')->nullable()->index()->constrained();
            $table->decimal('amount', 15, 2);
            $table->foreignId('bank_account_id')->index()->constrained('customer_bank_accounts');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
