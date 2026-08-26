<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->index()->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('sheba_number', 26);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_bank_accounts');
    }
};
