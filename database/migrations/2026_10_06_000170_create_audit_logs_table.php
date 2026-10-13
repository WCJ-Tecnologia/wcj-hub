<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs — docs/banco.md, 1. PLATFORM (G3, IA11, T8, T10).
 * Log imutável: só created_at, datetime not null (banco.md, seção 0).
 * Tabela única, imutável: nenhuma rota edita nem exclui (G3). Única exceção: a anonimização do
 * contato limpa os dados pessoais dele em changes (N12). Seção 9: before_data/after_data viram
 * changes; entram ai_authorization_id e outcome; sai user_agent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // null = token inválido, sem usuário. restrict: usuário com histórico não se exclui (G2).
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            // O canal: null = tela; preenchido = ia:<slug do assistente> (T10).
            $table->foreignId('ai_authorization_id')->nullable()->constrained('ai_authorizations')->restrictOnDelete();
            $table->string('action', 40);
            $table->string('outcome', 10);
            // Alias do morph map (AppServiceProvider).
            $table->string('auditable_type', 120);
            // null quando a negação vem antes de achar o registro.
            $table->unsignedBigInteger('auditable_id')->nullable();
            // {"campo": [antes, depois]}. Senha e token nunca entram.
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            // Log imutável: só created_at.
            $table->dateTime('created_at');

            $table->index(['auditable_type', 'auditable_id', 'created_at']);
            $table->index('user_id');
            $table->index('ai_authorization_id');
        });

        // CHECK do banco.md (o Blueprint do Laravel 12 não tem check()).
        DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_outcome_check CHECK (outcome IN ('success', 'denied'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
