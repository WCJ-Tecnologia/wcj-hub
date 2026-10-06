<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * contact_channels — docs/banco.txt, 1. PLATFORM (P52).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 * Domínio fechado: id fixo, semeado, sem tela. O código compara por slug.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('contact_channels')) {
            return;
        }

        Schema::create('contact_channels', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40);
            $table->string('label', 60);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_channels');
    }
};
