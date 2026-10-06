<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * task_types — docs/banco.txt, 2. PROJETOS (P17, P18, P52, R4).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 * Cadastrável, lista geral: o Administrador edita (G7).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('task_types')) {
            return;
        }

        Schema::create('task_types', function (Blueprint $table) {
            $table->id();
            // Só nos itens de que o código depende ('improvement', 'support'); os outros ficam nulos.
            $table->string('slug', 20)->nullable()->unique();
            $table->string('label', 60)->unique();
            $table->dateTime('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_types');
    }
};
