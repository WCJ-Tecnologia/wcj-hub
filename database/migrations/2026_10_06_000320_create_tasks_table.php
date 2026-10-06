<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * tasks — docs/banco.txt, 2. PROJETOS (P13–P28, P34, P39, P41, P45, P52, R8, R15).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 * "Tudo da tarefa é do mesmo projeto" (etapa, sprint, pacote, área, origem, dev e QA) é asserção
 * no Service, sem FK composta (R15, T7).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('tasks')) {
            return;
        }

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            // Sequencial por projeto (P16). A chave "SGM-17" = projects.key_prefix + number, sem coluna (T10).
            $table->unsignedInteger('number');
            $table->foreignId('board_stage_id')->constrained('board_stages')->restrictOnDelete();
            $table->foreignId('task_type_id')->constrained('task_types')->restrictOnDelete();
            $table->foreignId('project_area_id')->nullable()->constrained('project_areas')->restrictOnDelete();
            $table->foreignId('task_priority_id')->constrained('task_priorities')->restrictOnDelete();
            $table->foreignId('developer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('qa_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('proposed_hours', 6, 2)->nullable();
            // Ordem manual dentro da coluna (P13); a ordem padrão da tela não lê esta coluna.
            $table->unsignedInteger('position')->default(0);
            $table->string('title', 200);
            // Documento de requisição (P17).
            $table->string('story_as', 200)->nullable();
            $table->text('story_want')->nullable();
            $table->text('story_so_that')->nullable();
            $table->text('description')->nullable();
            // Teste cruzado (P34, R8).
            $table->foreignId('cross_tested_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('cross_tested_at')->nullable();
            // Data prevista da tarefa inteira (P28): foto, recalcula só ao reestimar.
            $table->date('due_on')->nullable();
            $table->foreignId('sprint_id')->nullable()->constrained('sprints')->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->restrictOnDelete();
            $table->foreignId('origin_task_id')->nullable()->constrained('tasks')->restrictOnDelete();
            // Card de suporte (P52).
            $table->foreignId('requester_contact_id')->nullable()->constrained('contacts')->restrictOnDelete();
            $table->foreignId('contact_channel_id')->nullable()->constrained('contact_channels')->restrictOnDelete();
            $table->dateTime('requested_at')->nullable();
            // Arquivamento do card com histórico (P26).
            $table->dateTime('archived_at')->nullable();
            $table->string('archive_reason', 500)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['project_id', 'number']);
            $table->index('project_id');
            $table->index('board_stage_id');
            $table->index('task_type_id');
            $table->index('project_area_id');
            $table->index('task_priority_id');
            $table->index('developer_id');
            $table->index('qa_id');
            $table->index('cross_tested_by');
            $table->index('sprint_id');
            $table->index('package_id');
            $table->index('origin_task_id');
            $table->index('requester_contact_id');
            $table->index('contact_channel_id');
            $table->index('due_on');
            $table->index('archived_at');
        });

        // CHECK do banco.txt (o Blueprint do Laravel 12 não tem check()).
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_estimated_hours_check CHECK (estimated_hours IS NULL OR estimated_hours > 0)');
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_proposed_hours_check CHECK (proposed_hours IS NULL OR proposed_hours > 0)');
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_archive_check CHECK ((archived_at IS NULL) = (archive_reason IS NULL))');
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_cross_test_check CHECK ((cross_tested_by IS NULL) = (cross_tested_at IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
