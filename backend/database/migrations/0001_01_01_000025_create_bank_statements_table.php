<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('statement_date');
            $table->string('source_file')->nullable();
            $table->enum('source_type', ['ofx', 'csv', 'txt', 'manual'])->default('manual');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->enum('status', ['imported', 'reconciled', 'closed'])->default('imported');
            $table->timestamps();

            $table->index('bank_account_id');
            $table->index('statement_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
