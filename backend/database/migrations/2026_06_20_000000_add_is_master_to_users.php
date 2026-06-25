<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_master')->default(false)->after('is_active');
            $table->boolean('is_system_admin')->default(false)->after('is_master');
            $table->string('master_token', 64)->nullable()->after('is_system_admin');
            $table->timestamp('last_master_login')->nullable()->after('master_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_master', 'is_system_admin', 'master_token', 'last_master_login']);
        });
    }
};