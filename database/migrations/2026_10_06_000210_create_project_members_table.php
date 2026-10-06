<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * project_members — docs/banco.txt, 2. PROJETOS (P1, P5).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('project_members')) {
            return;
        }

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['project_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
