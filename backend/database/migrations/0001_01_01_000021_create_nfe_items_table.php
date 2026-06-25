<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nfe_id')->constrained('nfe')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('ncm', 10)->nullable();
            $table->string('cfop', 10)->nullable();
            $table->string('description');
            $table->string('unit', 10)->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('icms', 15, 2)->default(0);
            $table->decimal('ipi', 15, 2)->default(0);
            $table->decimal('pis', 15, 2)->default(0);
            $table->decimal('cofins', 15, 2)->default(0);
            $table->timestamps();

            $table->index('nfe_id');
            $table->index('code');
            $table->index('ncm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfe_items');
    }
};
