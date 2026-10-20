<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card do quadro — docs/banco.md, seção 7 (13/10 e 14/10), aprovada por Lucas Scholz em 15/10.
 * Tudo aditivo:
 * - tasks.parent_task_id: card pai, um nível, do mesmo projeto (P57); o Service confere (R15);
 * - tasks.task_type_id e task_priority_id passam a aceitar nulo: o card rápido nasce só com o
 *   título (P17, decisão de 14/10);
 * - task_participants: quem acompanha além do dev e do QA (P58);
 * - task_types.color e project_areas.color: a cor da etiqueta, paleta fechada de 10 nomes
 *   (`TagColor` do kit); nulo = cinza.
 * A capa (tasks.cover_attachment_id) vem na migração seguinte, por causa da FK circular com
 * task_attachments (banco.md, tasks).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): cada parte confere se já foi aplicada.
        if (! Schema::hasColumn('tasks', 'parent_task_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('parent_task_id')->nullable()->after('origin_task_id')->constrained('tasks')->restrictOnDelete();
                $table->index('parent_task_id');
            });
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('task_type_id')->nullable()->change();
            $table->unsignedBigInteger('task_priority_id')->nullable()->change();
        });

        if (! Schema::hasTable('task_participants')) {
            Schema::create('task_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->dateTime('created_at');
                $table->dateTime('updated_at');

                $table->unique(['task_id', 'user_id']);
                $table->index('user_id');
            });
        }

        if (! Schema::hasColumn('task_types', 'color')) {
            Schema::table('task_types', function (Blueprint $table) {
                $table->string('color', 10)->nullable()->after('label');
            });
        }

        if (! Schema::hasColumn('project_areas', 'color')) {
            Schema::table('project_areas', function (Blueprint $table) {
                $table->string('color', 10)->nullable()->after('name');
            });
        }
    }

    /**
     * Em dev: volta o schema. A volta a not null de task_type_id e task_priority_id só passa com a
     * checagem de FK desligada na sessão (o MySQL recusa mudar coluna que está numa FK, erro 1832;
     * a FK fica como está). Card sem tipo ou sem prioridade impede a volta (o not null recusa).
     * DDL não é transacional: o change() vem primeiro, para uma recusa deixar o banco como estava,
     * e as cores e a tabela saem por último (Verifier do B3 PR 1, rodada 1, Fix 1).
     */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(fn () => Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('task_type_id')->nullable(false)->change();
            $table->unsignedBigInteger('task_priority_id')->nullable(false)->change();
        }));

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['parent_task_id']);
            $table->dropIndex(['parent_task_id']);
            $table->dropColumn('parent_task_id');
        });

        Schema::dropIfExists('task_participants');
        Schema::table('task_types', fn (Blueprint $table) => $table->dropColumn('color'));
        Schema::table('project_areas', fn (Blueprint $table) => $table->dropColumn('color'));
    }
};
