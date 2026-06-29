<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_fornecedores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('codigo_legado')->nullable()->index();
            $table->string('nome')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('historico_contas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('codigo_legado')->nullable()->index();
            $table->string('nome')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('historico_notas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('codigo_legado')->nullable()->index();
            $table->string('numero')->nullable()->index();
            $table->date('emissao')->nullable()->index();
            $table->decimal('valor', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_notas');
        Schema::dropIfExists('historico_contas');
        Schema::dropIfExists('historico_fornecedores');
    }
};
