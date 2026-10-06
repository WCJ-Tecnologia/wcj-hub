<?php

namespace App\Console\Commands;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAttachment;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Support\Exemplo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Apaga o dado de exemplo (playbooks/aplicacao-nova.md, etapa 8): só o que tem a marca de
 * App\Support\Exemplo. Projeto de exemplo sai inteiro, com cards, horas e anexos (arquivo
 * inclusive). Pessoa de exemplo com histórico fora dos projetos de exemplo (autoria em
 * audit_logs, por exemplo) não se exclui: é desativada (G2). A auditoria nunca é apagada (G3).
 */
class LimparExemploCommand extends Command
{
    protected $signature = 'app:limpar-exemplo {--force : Não pergunta (obrigatório em produção)}';

    protected $description = 'Apaga o dado de exemplo (nome "[Exemplo] …", e-mail @exemplo.wcj.test)';

    public function handle(): int
    {
        $clientes = Client::query()->where('name', 'like', Exemplo::likeNome())->pluck('id');
        $pessoas = User::query()->where('email', 'like', Exemplo::likeEmail())->pluck('id');
        $projetos = Project::query()
            ->whereIn('client_id', $clientes)
            ->orWhere('name', 'like', Exemplo::likeNome())
            ->pluck('id');

        if ($clientes->isEmpty() && $pessoas->isEmpty() && $projetos->isEmpty()) {
            $this->info('Nenhum dado de exemplo encontrado.');

            return self::SUCCESS;
        }

        $this->line("Exemplo: {$clientes->count()} cliente(s), {$projetos->count()} projeto(s), {$pessoas->count()} pessoa(s).");

        if (! $this->option('force') && (app()->isProduction() || ! $this->confirm('Apagar?', true))) {
            $this->warn(app()->isProduction() ? 'Em produção, rode com --force.' : 'Nada foi apagado.');

            return self::FAILURE;
        }

        $arquivos = [];
        $desativadas = 0;

        DB::transaction(function () use ($clientes, $pessoas, $projetos, &$arquivos, &$desativadas) {
            $tarefas = Task::query()->whereIn('project_id', $projetos)->pluck('id');
            $arquivos = TaskAttachment::query()->whereIn('task_id', $tarefas)->pluck('path')->all();

            // Filhos antes dos pais; os cascades do banco cuidam do resto.
            foreach (['time_entries', 'task_attachments', 'task_comments', 'task_pull_requests', 'task_stage_movements'] as $tabela) {
                DB::table($tabela)->whereIn('task_id', $tarefas)->delete();
            }
            // Critério e tarefa se apontam (subitem, bug de produção): quebra os laços antes.
            DB::table('task_acceptance_criteria')->whereIn('task_id', $tarefas)->whereNotNull('parent_id')->delete();
            DB::table('tasks')->whereIn('id', $tarefas)->update(['origin_task_id' => null]);
            DB::table('tasks')->whereIn('id', $tarefas)->delete();
            foreach (['sprints', 'packages', 'project_members', 'project_areas'] as $tabela) {
                DB::table($tabela)->whereIn('project_id', $projetos)->delete();
            }
            DB::table('board_stages')->whereIn('project_id', $projetos)->delete();
            DB::table('projects')->whereIn('id', $projetos)->delete();
            Contact::query()->whereIn('client_id', $clientes)->delete();
            Client::query()->whereIn('id', $clientes)->delete();

            foreach (User::query()->whereIn('id', $pessoas)->get() as $pessoa) {
                if (! $pessoa->deleteIfUnused()) {
                    $pessoa->setActive(false, null)->save();
                    $desativadas++;
                }
            }
        });

        // O cascade do banco não apaga arquivo (banco.md §4): depois do commit, do disco.
        if ($arquivos !== []) {
            Storage::delete($arquivos);
        }

        $this->info('Dado de exemplo apagado.'.($desativadas > 0 ? " {$desativadas} pessoa(s) com histórico ficaram desativadas." : ''));

        return self::SUCCESS;
    }
}
