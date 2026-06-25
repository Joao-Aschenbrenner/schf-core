<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained('bank_statements')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('description');
            $table->string('document_id')->nullable();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->foreignId('payable_id')->nullable()->constrained('payables')->nullOnDelete();
            $table->foreignId('pre_launch_id')->nullable()->constrained('pre_launches')->nullOnDelete();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('bank_statement_id');
            $table->index('transaction_date');
            $table->index('is_reconciled');
            $table->index('payable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_items');
    }
};
