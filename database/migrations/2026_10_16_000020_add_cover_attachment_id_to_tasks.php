<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tasks.cover_attachment_id — docs/banco.md, tasks e seção 7 (13/10): a capa do card, um anexo
 * de imagem da própria tarefa (P59; o Service confere task_id e mime_type). Migração própria,
 * depois de task_attachments, pela FK circular (task_attachments.task_id → tasks). Apagar o
 * anexo limpa a capa (set null).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5).
        if (Schema::hasColumn('tasks', 'cover_attachment_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('cover_attachment_id')->nullable()->after('parent_task_id')->constrained('task_attachments')->nullOnDelete();
            $table->index('cover_attachment_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tasks', 'cover_attachment_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['cover_attachment_id']);
            $table->dropIndex(['cover_attachment_id']);
            $table->dropColumn('cover_attachment_id');
        });
    }
};
