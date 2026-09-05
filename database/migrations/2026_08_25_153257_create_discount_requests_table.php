<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->index()->constrained();
            $table->foreignId('customer_id')->index()->constrained();
            $table->foreignId('requested_by')->index()->constrained('users');
            $table->decimal('proposed_amount', 15, 2);
            $table->decimal('final_amount', 15, 2)->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->index()->constrained('users');
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_requests');
    }
};
