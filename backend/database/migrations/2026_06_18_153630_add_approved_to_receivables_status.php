<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'received', 'cancelled'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending')->change();
        });
    }
};