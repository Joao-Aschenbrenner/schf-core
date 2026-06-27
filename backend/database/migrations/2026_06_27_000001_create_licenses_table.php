<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->enum('type', ['trial', 'community', 'enterprise'])->default('community');
            $table->enum('status', ['active', 'suspended', 'revoked', 'expired'])->default('active');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_cnpj', 14)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->integer('validation_count')->default(0);
            $table->integer('max_activations')->default(1);
            $table->integer('activation_count')->default(0);
            $table->json('metadata')->nullable();
            $table->json('features')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'expires_at']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
