<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('document_number')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('nfe_id')->nullable()->constrained('nfe')->nullOnDelete();
            $table->foreignId('health_plan_id')->nullable()->constrained('health_plans')->nullOnDelete();
            $table->foreignId('resource_plan_id')->nullable()->constrained('resource_plans')->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('interest', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->date('paid_at')->nullable();
            $table->enum('status', ['draft', 'pending', 'scheduled', 'paid', 'cancelled', 'overdue'])->default('pending');
            $table->enum('payment_method', ['boleto', 'transfer', 'pix', 'check', 'cash', 'deduction', 'other'])->nullable();
            $table->string('bar_code')->nullable();
            $table->string('payment_line_code')->nullable();
            $table->string('receipt_number')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('due_date');
            $table->index('supplier_id');
            $table->index('health_plan_id');
            $table->index('expense_category_id');
            $table->index('bank_account_id');
            $table->index('payment_date');
            $table->index('nfe_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
