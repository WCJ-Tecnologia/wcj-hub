<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * stage_roles — docs/banco.md, 2. PROJETOS (P10, P11).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 * Domínio fechado (P11): todo indicador lê o papel, nunca o nome da etapa. Os ids 1 (queue),
 * 2 (sprint) e 8 (support) são lidos por board_stages.single_role_key: fixos na semente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('stage_roles')) {
            return;
        }

        Schema::create('stage_roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40);
            $table->string('label', 60);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_roles');
    }
};
