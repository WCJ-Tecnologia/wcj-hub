<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * rework_reasons.slug — docs/banco.txt, 2. PROJETOS (P40) e seção 7 (07/10).
 * O "Outro" existe sempre (não se desativa, não se exclui, sempre pede texto) e o código precisa
 * achá-lo sem depender do nome, que o Administrador renomeia: mesmo padrão de task_types
 * ('improvement', 'support'). Só o Outro tem slug; os outros itens nascem nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasColumn('rework_reasons', 'slug')) {
            return;
        }

        Schema::table('rework_reasons', function (Blueprint $table) {
            $table->string('slug', 20)->nullable()->unique()->after('id');
        });

        // Banco já semeado antes desta migração: o Outro é o único que exige texto na semente.
        DB::table('rework_reasons')
            ->whereNull('slug')
            ->where('label', 'Outro')
            ->where('requires_text', true)
            ->update(['slug' => 'other']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('rework_reasons', 'slug')) {
            return;
        }

        Schema::table('rework_reasons', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
