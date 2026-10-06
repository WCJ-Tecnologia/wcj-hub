<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * sprints — docs/banco.txt, 2. PROJETOS (P45–P51, R5, R9).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('sprints')) {
            return;
        }

        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->string('name', 80);
            $table->string('goal', 500)->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            // null = aberta. Uma aberta por projeto é regra do Service (P45, T1).
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['project_id', 'name']);
        });

        // CHECK do banco.txt (o Blueprint do Laravel 12 não tem check()).
        DB::statement('ALTER TABLE sprints ADD CONSTRAINT sprints_dates_check CHECK (ends_on >= starts_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sprints');
    }
};
