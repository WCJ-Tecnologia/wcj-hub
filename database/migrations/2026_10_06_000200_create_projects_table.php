<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * projects — docs/banco.md, 2. PROJETOS (P1–P3, P7–P9, P15, P16, P46, T4).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('projects')) {
            return;
        }

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            // P1: definido na criação, não muda.
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            // Gestor responsável (P1); os demais gestores são os alocados com papel project_manager.
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            // P3: congela após a 1ª tarefa (Service).
            $table->string('key_prefix', 10)->unique();
            $table->string('name', 160);
            $table->date('starts_on');
            // Só informativa (P2, P9); null = produto contínuo.
            $table->date('due_on')->nullable();
            $table->dateTime('finished_at')->nullable();
            // Contador da chave (P16, T4): UPDATE ... LAST_INSERT_ID(last_task_number + 1).
            $table->unsignedInteger('last_task_number')->default(0);
            // Padrões do projeto (G1): mudam dali para a frente (P8).
            $table->boolean('require_pause_reason')->default(true);
            $table->unsignedSmallInteger('sprint_days')->default(10);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('client_id');
            $table->index('manager_id');
        });

        // CHECK do banco.md (o Blueprint do Laravel 12 não tem check()).
        DB::statement('ALTER TABLE projects ADD CONSTRAINT projects_due_on_check CHECK (due_on IS NULL OR due_on >= starts_on)');
        DB::statement('ALTER TABLE projects ADD CONSTRAINT projects_sprint_days_check CHECK (sprint_days > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
