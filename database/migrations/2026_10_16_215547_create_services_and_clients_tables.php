<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Catálogo de tratamientos de estética (visibles en la web)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->decimal('price', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(21.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Ficha de clientes
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('tax_id', 20)->nullable()->index(); // NIF/NIE
            $table->string('tax_id_type', 2)->default('02');    // 02 = NIF/NIE
            $table->string('name');
            $table->string('phone', 20)->index();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->char('country_code', 2)->default('ES');
            $table->text('aesthetic_notes')->nullable(); // Alergias, tratamientos previos
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
        Schema::dropIfExists('services');
    }
};
