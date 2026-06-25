<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adicionar legacy_id na tabela users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->after('id');
            $table->index('legacy_id');
        });

        // Adicionar legacy_type na tabela bank_accounts
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('legacy_type', 20)->nullable()->after('legacy_id');
        });

        // Adicionar source na tabela bank_statements (para distinguir legado de importação)
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->string('source', 30)->nullable()->after('source_type');
        });

        // Corrigir payment_method enum para incluir 'bank_slip' (legado usa 'boleto')
        // Nota: MySQL não permite alterar enum diretamente, mas 'boleto' já cobre isso
        // Adicionar campo bank_slip se necessário
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['legacy_id']);
            $table->dropColumn('legacy_id');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('legacy_type');
        });

        Schema::table('bank_statements', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
