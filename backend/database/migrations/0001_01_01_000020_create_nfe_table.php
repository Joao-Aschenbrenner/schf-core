<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfe', function (Blueprint $table) {
            $table->id();
            $table->string('nfe_key', 44)->unique()->nullable();
            $table->string('nfe_number')->index();
            $table->string('serie', 10)->nullable();
            $table->date('emission_date');
            $table->date('entry_date')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('health_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resource_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('goods_value', 15, 2)->default(0);
            $table->decimal('service_value', 15, 2)->default(0);
            $table->decimal('insurance_value', 15, 2)->default(0);
            $table->decimal('other_value', 15, 2)->default(0);
            $table->decimal('icms_value', 15, 2)->default(0);
            $table->decimal('ipi_value', 15, 2)->default(0);
            $table->decimal('pis_value', 15, 2)->default(0);
            $table->decimal('cofins_value', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->text('xml_content')->nullable();
            $table->enum('status', ['pending', 'classified', 'linked', 'cancelled'])->default('pending');
            $table->boolean('is_manual_entry')->default(false);
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('emission_date');
            $table->index('status');
            $table->index('supplier_id');
            $table->index('health_plan_id');
            $table->index('expense_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfe');
    }
};
