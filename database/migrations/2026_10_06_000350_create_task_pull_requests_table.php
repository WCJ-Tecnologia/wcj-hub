<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * task_pull_requests — docs/banco.txt, 2. PROJETOS (P23).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('task_pull_requests')) {
            return;
        }

        Schema::create('task_pull_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('url', 500);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['task_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_pull_requests');
    }
};
