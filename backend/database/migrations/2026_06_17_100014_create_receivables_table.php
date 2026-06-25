<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('description', 255);
            $table->string('document_number', 50)->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('interest', 15, 2)->default(0);
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->date('due_date');
            $table->date('receipt_date')->nullable();
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
            $table->string('payment_method', 30)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('legacy_nota_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id');
            $table->index('bank_account_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};
