<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit', 'transfer', 'investment', 'investment_redemption']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('document', 50)->nullable();
            $table->date('operation_date');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->foreignId('payable_id')->nullable()->constrained('payables')->nullOnDelete();
            $table->foreignId('receivable_id')->nullable()->constrained('receivables')->nullOnDelete();
            $table->foreignId('bank_investment_id')->nullable()->constrained('bank_investments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('bank_account_id');
            $table->index('type');
            $table->index('operation_date');
            $table->index('reference_type');
            $table->index('payable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_operations');
    }
};
