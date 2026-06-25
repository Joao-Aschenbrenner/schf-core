<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cnpj')->unique()->nullable();
            $table->string('cpf')->unique()->nullable();
            $table->string('trade_name')->nullable();
            $table->string('ie')->nullable();
            $table->string('im')->nullable();
            $table->string('cnae')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('cellphone')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_number')->nullable();
            $table->string('address_complement')->nullable();
            $table->string('address_district')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state', 2)->nullable();
            $table->string('address_zip', 9)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_agency')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_type', 10)->default('checking');
            $table->string('pix_key')->nullable();
            $table->string('pix_type', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('cnpj');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
