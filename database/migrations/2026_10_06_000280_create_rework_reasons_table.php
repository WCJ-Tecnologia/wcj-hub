<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * rework_reasons — docs/banco.txt, 2. PROJETOS (P40, R11).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 * Cadastrável, lista geral do Administrador (G7, R11).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('rework_reasons')) {
            return;
        }

        Schema::create('rework_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('label', 80)->unique();
            // O "outro" (P40).
            $table->boolean('requires_text')->default(false);
            $table->dateTime('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rework_reasons');
    }
};
