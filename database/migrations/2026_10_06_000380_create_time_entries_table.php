<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * time_entries — docs/banco.txt, 2. PROJETOS (P30, P31, P53).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('time_entries')) {
            return;
        }

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('worked_on');
            $table->decimal('hours', 6, 2);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('task_id');
            $table->index(['user_id', 'worked_on']);
        });

        // CHECK do banco.txt (o Blueprint do Laravel 12 não tem check()).
        DB::statement('ALTER TABLE time_entries ADD CONSTRAINT time_entries_hours_check CHECK (hours > 0 AND hours <= 24)');
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
