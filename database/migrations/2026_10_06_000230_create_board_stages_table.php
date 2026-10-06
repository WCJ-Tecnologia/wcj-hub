<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * board_stages — docs/banco.txt, 2. PROJETOS (P8, P10–P12, T1).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('board_stages')) {
            return;
        }

        Schema::create('board_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('stage_role_id')->constrained('stage_roles')->restrictOnDelete();
            $table->string('name', 60);
            $table->smallInteger('position');
            // "Remover" arquiva: a etapa segue nos movimentos antigos (P8, P12).
            $table->dateTime('archived_at')->nullable();
            // No máximo 1 etapa ativa de papel queue (1), sprint (2) e support (8) por projeto (T1, P11).
            // Compara pelo id porque coluna gerada não lê outra tabela.
            $table->unsignedBigInteger('single_role_key')->storedAs('IF(archived_at IS NULL AND stage_role_id IN (1, 2, 8), stage_role_id, NULL)');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['project_id', 'single_role_key']);
            $table->index('project_id');
            $table->index('stage_role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_stages');
    }
};
