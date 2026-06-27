<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_history', function (Blueprint $table) {
            $table->id();
            $table->string('from_version', 20);
            $table->string('to_version', 20);
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'rolled_back'])
                  ->default('pending');
            $table->string('method', 50)->default('docker_pull');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('user_id')->nullable();
            $table->string('rollback_to_version', 20)->nullable();
            $table->timestamps();

            $table->index(['to_version']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_history');
    }
};