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
        // 1. Cabecera de facturas
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('series', 20);
            $table->unsignedInteger('number');
            $table->enum('invoice_type', ['F1', 'F2', 'R1', 'R2', 'R3', 'R4', 'R5']);
            $table->date('issue_date');
            $table->time('issue_time');
            $table->string('issuer_tax_id', 20);
            $table->foreignId('client_id')->nullable()->constrained('clients')->restrictOnDelete();
            
            // Rectificativas
            $table->foreignId('rectified_invoice_id')->nullable()->constrained('invoices')->restrictOnDelete();
            $table->string('rectification_reason', 2)->nullable();

            // Totales
            $table->decimal('total_tax_base', 12, 2)->default(0.00);
            $table->decimal('total_tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_invoice', 12, 2)->default(0.00);

            // VeriFactu / Chaining
            $table->foreignId('previous_invoice_id')->nullable()->constrained('invoices')->restrictOnDelete();
            $table->char('previous_hash', 64)->nullable();
            $table->char('chain_hash', 64);
            $table->text('qr_payload');

            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['series', 'number']);
        });

        // 2. Líneas de detalle
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->string('concept');
            $table->decimal('quantity', 8, 2)->default(1.00);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_rate', 5, 2);

            $table->unique(['invoice_id', 'line_number']);
        });

        // 3. Desglose de impuestos (4FN)
        Schema::create('invoice_tax_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('tax_type', 2)->default('01'); // 01 = IVA
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('tax_base', 12, 2);
            $table->decimal('tax_amount', 12, 2);

            $table->unique(['invoice_id', 'tax_type', 'tax_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_tax_breakdowns');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
