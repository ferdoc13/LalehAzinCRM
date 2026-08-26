<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->index()->constrained();
            $table->string('phone', 11)->nullable()->index();
            $table->string('code', 6);
            $table->timestamp('expires_at')->index();
            $table->boolean('is_used')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
