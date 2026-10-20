<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quem colou o link do PR, quem enviou o anexo, quem propôs a reestimativa e quem arquivou: o que
 * a ficha do kit mostra (`added_by`, `uploaded_by`, `proposed_by`, `archived.by`) e o banco não
 * guardava (spec b3-ficha, MIG-04; aprovada por Lucas Scholz em 20/10, docs/banco.md seção 7,
 * diferença de 16/10). O módulo não lê audit_logs (A9). Tudo aditivo e nulo: as linhas de antes não
 * têm o dado, e a tela mostra "—".
 */
return new class extends Migration
{
    /** @var list<array{0: string, 1: string, 2: ?string}> tabela, coluna, depois de qual coluna */
    private const COLUMNS = [
        ['task_pull_requests', 'user_id', null],
        ['task_attachments', 'user_id', null],
        ['tasks', 'proposed_by', null],
        ['tasks', 'archived_by', 'archive_reason'],
    ];

    public function up(): void
    {
        // Idempotente (banco.md §5): cada coluna confere se já existe.
        foreach (self::COLUMNS as [$table, $column, $after]) {
            if (! Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $t) use ($column, $after) {
                    $definition = $t->foreignId($column)->nullable();
                    if ($after !== null) {
                        $definition->after($after);
                    }
                    $definition->constrained('users')->restrictOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as [$table, $column]) {
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->dropForeign([$column]);
                    $t->dropColumn($column);
                });
            }
        }
    }
};
