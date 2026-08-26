<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_code', 10)->nullable()->index();
            $table->string('mobile', 11)->unique();
            $table->text('address')->nullable();
            $table->foreignId('employee_id')->index()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
