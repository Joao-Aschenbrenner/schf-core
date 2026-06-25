<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('canonical_agency')->nullable()->after('legacy_type');
            $table->string('canonical_account')->nullable()->after('canonical_agency');
            $table->enum('classification', ['corrente', 'aplicacao', 'caixa_interno', 'hibrida', 'fechada'])->nullable()->after('canonical_account');

            $table->index('canonical_agency');
            $table->index('canonical_account');
            $table->index('classification');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropIndex(['canonical_agency']);
            $table->dropIndex(['canonical_account']);
            $table->dropIndex(['classification']);
            $table->dropColumn(['canonical_agency', 'canonical_account', 'classification']);
        });
    }
};
