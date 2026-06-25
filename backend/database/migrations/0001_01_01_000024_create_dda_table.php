<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dda', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code', 10)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('document_number')->nullable();
            $table->string('title_number')->nullable();
            $table->string('bar_code')->nullable();
            $table->string('payment_line_code')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_cnpj')->nullable();
            $table->string('payer_cpf')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('nfe_id')->nullable()->constrained('nfe')->nullOnDelete();
            $table->foreignId('payable_id')->nullable()->constrained('payables')->nullOnDelete();
            $table->enum('status', ['imported', 'identified', 'linked', 'rejected', 'expired'])->default('imported');
            $table->text('notes')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('due_date');
            $table->index('supplier_id');
            $table->index('payer_cnpj');
            $table->index('payer_cpf');
            $table->index('nfe_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dda');
    }
};
