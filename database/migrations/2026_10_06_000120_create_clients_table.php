<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * clients — docs/banco.md, 1. PLATFORM (N5, N7, N11).
 * created_at/updated_at: datetime not null (banco.md, seção 0); datetimes() do Laravel os faria nulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente (banco.md §5): pode rodar sobre banco restaurado.
        if (Schema::hasTable('clients')) {
            return;
        }

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->char('country', 2)->default('BR');
            $table->string('document', 30)->nullable();
            $table->dateTime('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(['country', 'document']);
            $table->index('deactivated_at');
        });

        // CHECK do banco.md (o Blueprint do Laravel 12 não tem check()).
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_document_br_check CHECK (country <> 'BR' OR document IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
