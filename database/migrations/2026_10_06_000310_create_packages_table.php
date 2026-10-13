<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * packages — docs/banco.md, 2. PROJETOS (P41–P44, R10).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('packages')) {
            return;
        }

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->string('name', 40);
            $table->date('planned_release_on')->nullable();
            // Estado derivado das duas datas (P42, R10): sem coluna de status.
            $table->dateTime('homologation_started_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['project_id', 'name']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
