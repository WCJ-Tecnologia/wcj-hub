<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users — docs/banco.md, 1. PLATFORM (N1–N4, IA4).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 * Seção 9: saem document, phone, email_verified_at, last_login_at e remember_token do template;
 * email e password not null (N1: o admin convida).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('email', 160)->unique();
            $table->string('password', 255);
            $table->boolean('must_change_password')->default(true);
            $table->decimal('daily_hours', 6, 2)->nullable();
            $table->dateTime('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->boolean('ai_write_allowed')->default(true);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('role_id');
            $table->index('deactivated_at');
        });

        // CHECK do banco.md (o Blueprint do Laravel 12 não tem check()).
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_daily_hours_check CHECK (daily_hours IS NULL OR (daily_hours > 0 AND daily_hours <= 24))');
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
