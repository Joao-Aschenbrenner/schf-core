<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code', 10);
            $table->string('bank_name');
            $table->string('agency');
            $table->string('account');
            $table->string('digit', 5)->nullable();
            $table->enum('type', ['checking', 'savings', 'investment'])->default('checking');
            $table->string('holder_name')->nullable();
            $table->string('holder_cnpj')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->foreignId('health_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('bank_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
