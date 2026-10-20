<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * task_types passa a ser DO PROJETO — docs/banco.md, seção 7 (14/10), aprovada por Lucas Scholz
 * em 15/10. REESCREVE DADO (trilho completo, spec b3-quadro-e-card, MIG-01):
 * 1. project_id entra nulo;
 * 2. cada projeto ganha uma cópia de cada tipo geral (slug, rótulo, desativação) e o
 *    "Desenvolvimento" (development), se ainda não tem;
 * 3. cada tarefa passa a apontar para a cópia do próprio projeto com o mesmo rótulo;
 * 4. os tipos gerais saem; project_id vira not null; os uniques passam a valer por projeto
 *    (os gerais caem antes da cópia, que repete os rótulos).
 * Idempotente: rodar de novo não copia de novo (a cópia é por (projeto, rótulo)). Plano de volta:
 * o down() em dev; em produção, o backup (B10, ainda não há produção).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('task_types', 'project_id')) {
            Schema::table('task_types', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->cascadeOnDelete();
            });
        }

        // Os uniques gerais saem antes da cópia: senão a cópia colide com o próprio tipo geral.
        Schema::table('task_types', function (Blueprint $table) {
            if ($this->hasIndex('task_types_slug_unique')) {
                $table->dropUnique(['slug']);
            }
            if ($this->hasIndex('task_types_label_unique')) {
                $table->dropUnique(['label']);
            }
        });

        DB::transaction(function () {
            $now = now();
            $globals = DB::table('task_types')->whereNull('project_id')->orderBy('id')->get();
            $defaults = $globals->map(fn ($type) => [
                'slug' => $type->slug,
                'label' => $type->label,
                'color' => $type->color,
                'deactivated_at' => $type->deactivated_at,
                'deactivated_by' => $type->deactivated_by,
            ])->all();

            if (! $globals->contains('slug', 'development')) {
                $defaults[] = ['slug' => 'development', 'label' => 'Desenvolvimento', 'color' => null, 'deactivated_at' => null, 'deactivated_by' => null];
            }

            foreach (DB::table('projects')->orderBy('id')->pluck('id') as $projectId) {
                $existing = DB::table('task_types')->where('project_id', $projectId)->pluck('label')->all();
                $missing = array_filter($defaults, fn (array $type) => ! in_array($type['label'], $existing, true));

                DB::table('task_types')->insert(array_map(
                    fn (array $type) => [...$type, 'project_id' => $projectId, 'created_at' => $now, 'updated_at' => $now],
                    array_values($missing),
                ));
            }

            // Cada tarefa na cópia do próprio projeto, achada pelo rótulo do tipo geral.
            DB::statement(<<<'SQL'
                UPDATE tasks t
                JOIN task_types g ON g.id = t.task_type_id AND g.project_id IS NULL
                JOIN task_types c ON c.project_id = t.project_id AND c.label = g.label
                SET t.task_type_id = c.id
            SQL);

            DB::table('task_types')->whereNull('project_id')->delete();
        });

        Schema::table('task_types', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable(false)->change();
            if (! $this->hasIndex('task_types_project_id_slug_unique')) {
                $table->unique(['project_id', 'slug']);
            }
            if (! $this->hasIndex('task_types_project_id_label_unique')) {
                $table->unique(['project_id', 'label']);
            }
        });
    }

    /**
     * Em dev: volta à lista geral, um tipo por rótulo (o mais antigo), com as tarefas apontando
     * para ele. Cor e desativação ficam as do mais antigo de cada rótulo.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('task_types', 'project_id')) {
            return;
        }

        Schema::table('task_types', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropUnique(['project_id', 'slug']);
            $table->dropUnique(['project_id', 'label']);
        });

        DB::transaction(function () {
            DB::statement(<<<'SQL'
                UPDATE tasks t
                JOIN task_types c ON c.id = t.task_type_id
                JOIN (SELECT label, MIN(id) AS keep_id FROM task_types GROUP BY label) k ON k.label = c.label
                SET t.task_type_id = k.keep_id
            SQL);
            DB::statement(<<<'SQL'
                DELETE c FROM task_types c
                JOIN (SELECT label, MIN(id) AS keep_id FROM task_types GROUP BY label) k ON k.label = c.label
                WHERE c.id <> k.keep_id
            SQL);
        });

        Schema::table('task_types', function (Blueprint $table) {
            $table->dropColumn('project_id');
            $table->unique('slug');
            $table->unique('label');
        });
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('task_types'))->contains('name', $name);
    }
};
