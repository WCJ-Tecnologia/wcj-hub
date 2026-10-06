<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * task_acceptance_criteria — docs/banco.txt, 2. PROJETOS (P17, P36, P37, T9).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('task_acceptance_criteria')) {
            return;
        }

        Schema::create('task_acceptance_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('task_acceptance_criteria')->cascadeOnDelete();
            // O n do CA_n, gravado ao criar e estável: excluir não renumera (T9).
            $table->unsignedSmallInteger('number');
            // Só para o unique: no MySQL, null não colide (T9). VIRTUAL, não STORED: parent_id tem FK
            // com cascade, e coluna-base de STORED não aceita isso (erro 1215).
            $table->unsignedBigInteger('parent_key')->virtualAs('IFNULL(parent_id, 0)');
            $table->text('description');
            $table->foreignId('test_result_id')->default(1)->constrained('test_results')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['task_id', 'parent_key', 'number']);
            $table->index('task_id');
            $table->index('parent_id');
            $table->index('test_result_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_acceptance_criteria');
    }
};
