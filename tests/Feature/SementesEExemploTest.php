<?php

namespace Tests\Feature;

use App\Platform\Models\Client;
use App\Platform\Models\User;
use App\Support\Exemplo;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ExemploSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Sementes do banco.md e o dado de exemplo marcado, com o comando que o apaga. */
class SementesEExemploTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_closed_domains_and_initial_lists_are_seeded_and_a_second_run_changes_nothing(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $slugs = fn (string $tabela) => DB::table($tabela)->orderBy('id')->pluck('slug', 'id')->all();

        $this->assertSame([1 => 'admin', 2 => 'project_manager', 3 => 'developer', 4 => 'sales'], $slugs('roles'));
        $this->assertSame([1 => 'queue', 2 => 'sprint', 3 => 'development', 4 => 'code_review', 5 => 'test', 6 => 'done', 7 => 'paused', 8 => 'support'], $slugs('stage_roles'));
        $this->assertSame([1 => 'low', 2 => 'normal', 3 => 'high', 4 => 'urgent'], $slugs('task_priorities'));
        $this->assertSame([1 => 'pending', 2 => 'passed', 3 => 'failed'], $slugs('test_results'));
        $this->assertSame([1 => 'code_review', 2 => 'cross_test', 3 => 'qa', 4 => 'homologation'], $slugs('validation_types'));
        $this->assertSame([1 => 'whatsapp', 2 => 'phone', 3 => 'email', 4 => 'meeting', 5 => 'other'], $slugs('contact_channels'));
        $this->assertSame([1 => 'claude', 2 => 'chatgpt', 3 => 'codex', 4 => 'gpt'], $slugs('ai_assistants'));
        $this->assertFalse((bool) DB::table('ai_assistants')->where('slug', 'chatgpt')->value('write_allowed'));

        $this->assertSame(['Melhoria', 'Correção de bug', 'Suporte', 'Mudança (GMUD)'], DB::table('task_types')->orderBy('id')->pluck('label')->all());
        $this->assertSame(['improvement', 'support'], DB::table('task_types')->whereNotNull('slug')->orderBy('id')->pluck('slug')->all());
        $this->assertSame(5, DB::table('rework_reasons')->count());
        $this->assertSame(['Outro'], DB::table('rework_reasons')->where('requires_text', true)->pluck('label')->all());
        $this->assertSame(['other'], DB::table('rework_reasons')->whereNotNull('slug')->pluck('slug')->all(), 'só o Outro tem slug (P40)');

        $wcj = Client::query()->where('document', Client::WCJ_CNPJ)->sole();
        $this->assertSame(['WCJ Tecnologia', 'BR'], [$wcj->name, $wcj->country]);
        $this->assertSame(0, User::query()->count(), 'o admin vem do app:create-admin, nunca de seeder');
    }

    public function test_a_renamed_list_item_is_not_recreated_by_a_new_run(): void
    {
        $this->seed(DatabaseSeeder::class);
        DB::table('task_types')->where('label', 'Correção de bug')->update(['label' => 'Bug']);
        DB::table('rework_reasons')->where('label', 'Ajuste visual')->update(['label' => 'Visual']);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, DB::table('task_types')->count());
        $this->assertSame(5, DB::table('rework_reasons')->count());
    }

    public function test_the_example_data_is_marked_and_the_command_removes_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(ExemploSeeder::class);

        $this->assertSame(1, Client::query()->where('name', 'like', Exemplo::likeNome())->count());
        $this->assertSame(3, User::query()->where('email', 'like', Exemplo::likeEmail())->count());
        $this->assertSame(3, DB::table('tasks')->count());
        $this->assertSame(3, (int) DB::table('projects')->where('key_prefix', 'EXE')->value('last_task_number'));
        $this->assertSame(
            ['Support', 'Backlog', 'To do', 'Paused', 'Doing', 'Code-review', 'In test', 'Done'],
            DB::table('board_stages')->orderBy('position')->pluck('name')->all(),
            'as 8 etapas na ordem da P10'
        );

        $this->artisan('app:limpar-exemplo', ['--force' => true])->assertSuccessful();

        foreach (['tasks', 'task_stage_movements', 'task_acceptance_criteria', 'board_stages', 'project_members', 'projects', 'contacts'] as $tabela) {
            $this->assertSame(0, DB::table($tabela)->count(), $tabela);
        }
        $this->assertSame(0, User::query()->count());
        $this->assertSame(['WCJ Tecnologia'], Client::query()->pluck('name')->all(), 'o que não é exemplo fica');
    }
}
