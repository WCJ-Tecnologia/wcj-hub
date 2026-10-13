<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_assistants — docs/banco.md, 1. PLATFORM (IA3, IA4, IA11).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 * Domínio fechado: id fixo, semeado, sem tela. O código compara por slug.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('ai_assistants')) {
            return;
        }

        Schema::create('ai_assistants', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40);
            $table->string('label', 60);
            $table->boolean('write_allowed');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_assistants');
    }
};
