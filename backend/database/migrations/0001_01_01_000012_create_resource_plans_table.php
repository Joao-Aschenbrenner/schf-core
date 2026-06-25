<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('used_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->decimal('available_amount', 15, 2)->storedAs('allocated_amount - used_amount - committed_amount');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('health_plan_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_plans');
    }
};
