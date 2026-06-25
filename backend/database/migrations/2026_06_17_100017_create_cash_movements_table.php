<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('document', 50)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('payable_id')->nullable()->constrained('payables')->nullOnDelete();
            $table->foreignId('receivable_id')->nullable()->constrained('receivables')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('cash_register_id');
            $table->index('type');
            $table->index('category');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
