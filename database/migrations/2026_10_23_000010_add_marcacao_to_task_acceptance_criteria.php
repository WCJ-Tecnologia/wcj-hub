<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "última marcação" do item do roteiro de QA (F4 §02: Fulano, DD/MM/AAAA às HH:MM): quem marcou
 * o resultado e quando, gravados a cada marcação e limpos quando o item volta sozinho para A testar
 * (edição do texto, retrabalho; P37). O antes e o depois de cada marcação seguem na auditoria (G3).
 * Spec b4-teste-e-qa; docs/banco.md seção 7, diferença de 23/10. O mesmo caminho das quatro colunas
 * de "quem" da ficha (2026_10_16_000040): aditivo e nulo, as linhas de antes mostram "—".
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): cada coluna confere se já existe.
        if (! Schema::hasColumn('task_acceptance_criteria', 'marked_by')) {
            Schema::table('task_acceptance_criteria', function (Blueprint $table) {
                $table->foreignId('marked_by')->nullable()->after('test_result_id')->constrained('users')->restrictOnDelete();
            });
        }
        if (! Schema::hasColumn('task_acceptance_criteria', 'marked_at')) {
            Schema::table('task_acceptance_criteria', function (Blueprint $table) {
                $table->dateTime('marked_at')->nullable()->after('marked_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('task_acceptance_criteria', 'marked_by')) {
            Schema::table('task_acceptance_criteria', function (Blueprint $table) {
                $table->dropForeign(['marked_by']);
                $table->dropColumn('marked_by');
            });
        }
        if (Schema::hasColumn('task_acceptance_criteria', 'marked_at')) {
            Schema::table('task_acceptance_criteria', function (Blueprint $table) {
                $table->dropColumn('marked_at');
            });
        }
    }
};
