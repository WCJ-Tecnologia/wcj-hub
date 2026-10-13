<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * task_attachments — docs/banco.md, 2. PROJETOS (P24, P36).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('task_attachments')) {
            return;
        }

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            // restrict: excluir anexo passa pelo Service, que apaga o arquivo e a linha juntos (banco.md §4).
            $table->foreignId('task_id')->constrained('tasks')->restrictOnDelete();
            // Preenchido = evidência do QA naquele item do roteiro (P36).
            $table->foreignId('task_acceptance_criterion_id')->nullable()->constrained('task_acceptance_criteria')->restrictOnDelete();
            $table->string('path', 255)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('task_id');
            $table->index('task_acceptance_criterion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};
