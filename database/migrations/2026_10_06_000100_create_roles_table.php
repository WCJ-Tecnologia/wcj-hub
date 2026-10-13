<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * roles — docs/banco.md, 1. PLATFORM (N2).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 * Domínio fechado (N2): 4 papéis fixos, ids da semente. O código compara por slug.
 * Seção 9: substitui a user_types do template.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('roles')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40);
            $table->string('label', 60);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
