<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->enum('status', ['draft', 'confirmed', 'paid', 'cancelled'])->default('draft');
            $table->string('provision_type', 50)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('legacy_nota_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id');
            $table->index('status');
            $table->index('due_date');
            $table->index('provision_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisions');
    }
};
