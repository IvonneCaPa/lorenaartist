<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            
            // Estados del flujo de trabajo
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])
                  ->default('confirmed');

            $table->text('notes')->nullable();          // Peticiones especiales o anotaciones
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete(); // Si ya se facturó
            $table->timestamps();

            // Índice compuesto para acelerar comprobaciones de solapamiento
            $table->index(['starts_at', 'ends_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};