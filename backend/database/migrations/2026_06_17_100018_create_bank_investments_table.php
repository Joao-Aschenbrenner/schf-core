<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('description', 255);
            $table->enum('investment_type', ['apl', 'aplicacao', 'investimento', 'cdb', 'lci_lca'])->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('yield_rate', 8, 4)->nullable();
            $table->date('start_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->enum('status', ['active', 'redeemed', 'closed'])->default('active');
            $table->decimal('redeemed_amount', 15, 2)->default(0);
            $table->date('redeemed_at')->nullable();
            $table->unsignedBigInteger('legacy_conta_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('bank_account_id');
            $table->index('status');
            $table->index('investment_type');
            $table->index('maturity_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_investments');
    }
};
