<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quem colou o link do PR, quem enviou o anexo, quem propôs a reestimativa e quem arquivou: o que
 * a ficha do kit mostra (`added_by`, `uploaded_by`, `proposed_by`, `archived.by`) e o banco não
 * guardava (spec b3-ficha, MIG-04; AGUARDA APROVAÇÃO). O módulo não lê audit_logs (A9). Tudo
 * aditivo e nulo: as linhas de antes não têm o dado, e a tela mostra "—".
 */
return new class extends Migration
{
    /** @var array<string, string> tabela => coluna */
    private const COLUMNS = [
        'task_pull_requests' => 'user_id',
        'task_attachments' => 'user_id',
        'tasks' => 'proposed_by',
    ];

    public function up(): void
    {
        // Idempotente (banco.md §5).
        foreach (self::COLUMNS as $table => $column) {
            if (! Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->foreignId($column)->nullable()->constrained('users')->restrictOnDelete();
                });
            }
        }

        if (! Schema::hasColumn('tasks', 'archived_by')) {
            Schema::table('tasks', function (Blueprint $t) {
                $t->foreignId('archived_by')->nullable()->after('archive_reason')->constrained('users')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ([...self::COLUMNS, 'tasks ' => 'archived_by'] as $table => $column) {
            $table = trim($table);
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->dropForeign([$column]);
                    $t->dropColumn($column);
                });
            }
        }
    }
};
