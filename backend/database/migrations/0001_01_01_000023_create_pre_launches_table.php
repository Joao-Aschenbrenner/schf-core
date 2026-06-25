<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_launches', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->enum('type', ['payroll', 'medical_fees', 'boleto', 'supplier', 'tax', 'other']);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('health_plan_id')->nullable()->constrained('health_plans')->nullOnDelete();
            $table->foreignId('resource_plan_id')->nullable()->constrained('resource_plans')->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->decimal('estimated_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->nullable();
            $table->date('expected_date');
            $table->date('actual_date')->nullable();
            $table->enum('status', ['projected', 'confirmed', 'converted', 'cancelled'])->default('projected');
            $table->foreignId('payable_id')->nullable()->constrained('payables')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
            $table->index('expected_date');
            $table->index('supplier_id');
            $table->index('health_plan_id');
            $table->index('bank_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_launches');
    }
};
