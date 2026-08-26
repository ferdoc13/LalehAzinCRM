<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credit_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->index()->constrained();
            $table->foreignId('discount_request_id')->nullable()->index()->constrained();
            $table->decimal('amount', 15, 2);
            $table->string('transaction_type')->index();
            $table->text('description')->nullable();
            $table->decimal('balance_after', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credit_ledgers');
    }
};
