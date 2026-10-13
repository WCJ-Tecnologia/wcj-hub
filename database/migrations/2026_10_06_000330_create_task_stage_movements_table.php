<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * task_stage_movements — docs/banco.md, 2. PROJETOS (P14, P15, P39, P40, R14).
 * Log imutável: só created_at, datetime not null (banco.md, seção 0).
 * Fonte dos indicadores. Imutável. As duas etapas são do projeto da tarefa: asserção no Service (R15).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('task_stage_movements')) {
            return;
        }

        Schema::create('task_stage_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('moved_by')->constrained('users')->restrictOnDelete();
            // O canal: null = tela; preenchido = ia:<slug do assistente> (IA11, R14).
            $table->foreignId('ai_authorization_id')->nullable()->constrained('ai_authorizations')->restrictOnDelete();
            // null = criação.
            $table->foreignId('from_board_stage_id')->nullable()->constrained('board_stages')->restrictOnDelete();
            $table->foreignId('to_board_stage_id')->constrained('board_stages')->restrictOnDelete();
            // Preenchido = é retrabalho (fonte única, P39).
            $table->foreignId('rework_origin_id')->nullable()->constrained('validation_types')->restrictOnDelete();
            $table->foreignId('rework_reason_id')->nullable()->constrained('rework_reasons')->restrictOnDelete();
            $table->string('reason_text', 500)->nullable();
            // Log imutável: só created_at (= momento do movimento).
            $table->dateTime('created_at');

            $table->index(['task_id', 'created_at']);
            $table->index('moved_by');
            $table->index('ai_authorization_id');
            $table->index('from_board_stage_id');
            $table->index('to_board_stage_id');
            $table->index('rework_origin_id');
            $table->index('rework_reason_id');
        });

        // CHECK do banco.md (o Blueprint do Laravel 12 não tem check()).
        DB::statement('ALTER TABLE task_stage_movements ADD CONSTRAINT task_stage_movements_rework_check CHECK ((rework_origin_id IS NULL) = (rework_reason_id IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('task_stage_movements');
    }
};
