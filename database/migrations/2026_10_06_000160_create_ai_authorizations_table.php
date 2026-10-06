<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_authorizations — docs/banco.txt, 1. PLATFORM (IA4, IA6, T1).
 * created_at/updated_at: datetime not null (banco.txt, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('ai_authorizations')) {
            return;
        }

        Schema::create('ai_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ai_assistant_id')->constrained('ai_assistants')->restrictOnDelete();
            // oauth_clients.id do Passport (uuid). Sem FK, como no banco.txt.
            $table->char('oauth_client_id', 36);
            $table->dateTime('revoked_at')->nullable();
            // Uma conexão ativa por usuário e assistente, travada no banco (T1); as revogadas não colidem.
            $table->unsignedBigInteger('active_assistant_id')->storedAs('IF(revoked_at IS NULL, ai_assistant_id, NULL)');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['user_id', 'active_assistant_id']);
            $table->index('ai_assistant_id');
            $table->index('oauth_client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_authorizations');
    }
};
