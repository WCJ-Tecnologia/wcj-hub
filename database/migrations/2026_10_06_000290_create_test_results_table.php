<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * test_results — docs/banco.md, 2. PROJETOS (P36, P37).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 * Domínio fechado: o id 1 (pending) é o default de task_acceptance_criteria.test_result_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('test_results')) {
            return;
        }

        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40);
            $table->string('label', 60);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
