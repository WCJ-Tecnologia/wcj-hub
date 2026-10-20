<?php

namespace App\Providers;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Package;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ProjectArea;
use App\Domains\Projetos\Models\ProjectMember;
use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Models\TaskAttachment;
use App\Domains\Projetos\Models\TaskComment;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Models\TaskPullRequest;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Models\TestResult;
use App\Domains\Projetos\Models\TimeEntry;
use App\Domains\Projetos\Models\ValidationType;
use App\Domains\Projetos\Policies\ProjectPolicy;
use App\Domains\Projetos\Policies\ReworkReasonPolicy;
use App\Domains\Projetos\Policies\SprintPolicy;
use App\Domains\Projetos\Policies\TaskCommentPolicy;
use App\Domains\Projetos\Policies\TaskPolicy;
use App\Domains\Projetos\Policies\TimeEntryPolicy;
use App\Domains\Projetos\Services\ProjectClientManagement;
use App\Http\Responses\ThrottledAuthResponse;
use App\Platform\Contracts\ClientManagement;
use App\Platform\Models\AiAssistant;
use App\Platform\Models\AiAuthorization;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\ContactChannel;
use App\Platform\Models\Role;
use App\Platform\Models\User;
use App\Platform\Policies\AuditLogPolicy;
use App\Platform\Policies\ClientPolicy;
use App\Platform\Policies\ContactPolicy;
use App\Platform\Policies\UserPolicy;
use App\Platform\Services\MemoizedClientManagement;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Passport entra no B0 só com as tabelas (docs/banco.md, seção 4). As rotas /oauth/*
        // ficam desligadas até o B8 (conexões de IA), que as liga com os escopos, o limitador
        // do /oauth/token (B9) e a tela de autorização.
        Passport::ignoreRoutes();

        // O Núcleo não conhece módulo (A9, ADR-001): pergunta pelo contrato. O B2 troca a
        // implementação vazia (B1) pela de Projetos no give(): feito no B2; a memória por requisição fica na frente.
        $this->app->scoped(ClientManagement::class, MemoizedClientManagement::class);
        $this->app->when(MemoizedClientManagement::class)->needs(ClientManagement::class)->give(ProjectClientManagement::class);
    }

    public function boot(): void
    {
        $this->registerMorphMap();
        // A memória do MemoizedClientManagement vale uma requisição. No FPM o processo já acaba
        // aí; num processo que atende várias (testes, Octane), a seguinte pergunta de novo.
        Event::listen(RequestHandled::class, fn () => $this->app->forgetInstance(ClientManagement::class));
        $this->registerPolicies();
        $this->registerCardBinding();
        $this->registerRateLimiters();
        $this->registerPasswordPolicy();
    }

    /**
     * "login" guards every credential endpoint: login, forgot, reset and first
     * access. Two keys: IP + account, and a ceiling per IP. Limits live in
     * config/auth.php so a test environment can loosen the per-IP ceiling.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $account = Str::lower(trim((string) $request->input('email', $request->user()?->email ?? '')));
            $response = fn (Request $request, array $headers) => (new ThrottledAuthResponse)($request, $headers);

            return [
                Limit::perMinute((int) config('auth.throttle.per_account', 5))
                    ->by('login:'.$request->ip().'|'.$account)
                    ->response($response),
                Limit::perMinute((int) config('auth.throttle.per_ip', 20))
                    ->by('login-ip:'.$request->ip())
                    ->response($response),
            ];
        });
    }

    private function registerPasswordPolicy(): void
    {
        Password::defaults(fn () => Password::min(10)->letters()->numbers());
    }

    /**
     * {card} nas rotas do quadro (B3) é a chave do card, "SGM-17" (P16), procurada só entre os
     * cards do projeto da rota: chave de outro projeto, prefixo errado ou número inexistente é 404,
     * antes da Policy.
     */
    private function registerCardBinding(): void
    {
        Route::bind('card', function (string $value, LaravelRoute $route) {
            $project = $route->parameter('project');
            $project = $project instanceof Project ? $project : Project::query()->findOrFail($project);

            if (! preg_match('/^(.+)-(\d+)$/', $value, $match) || strcasecmp($match[1], $project->key_prefix) !== 0) {
                throw (new ModelNotFoundException)->setModel(Task::class, [$value]);
            }

            return Task::query()->where('project_id', $project->id)->where('number', (int) $match[2])->firstOrFail()->setRelation('project', $project);
        });
    }

    /**
     * Alias de cada Model em audit_logs.auditable_type e notifications.notifiable_type
     * (docs/banco.md, audit_logs; ADR-001). enforce: Model fora do mapa falha em vez de
     * gravar o nome da classe, que muda quando o código muda de pasta. Model novo entra aqui
     * no mesmo commit.
     */
    private function registerMorphMap(): void
    {
        Relation::enforceMorphMap([
            // Platform
            'role' => Role::class,
            'user' => User::class,
            'client' => Client::class,
            'contact' => Contact::class,
            'contact_channel' => ContactChannel::class,
            'audit_log' => AuditLog::class,
            'ai_assistant' => AiAssistant::class,
            'ai_authorization' => AiAuthorization::class,
            // Projetos
            'project' => Project::class,
            'project_member' => ProjectMember::class,
            'stage_role' => StageRole::class,
            'board_stage' => BoardStage::class,
            'task_type' => TaskType::class,
            'project_area' => ProjectArea::class,
            'task_priority' => TaskPriority::class,
            'task' => Task::class,
            'task_stage_movement' => TaskStageMovement::class,
            'rework_reason' => ReworkReason::class,
            'task_acceptance_criterion' => TaskAcceptanceCriterion::class,
            'test_result' => TestResult::class,
            'validation_type' => ValidationType::class,
            'task_pull_request' => TaskPullRequest::class,
            'task_comment' => TaskComment::class,
            'task_attachment' => TaskAttachment::class,
            'time_entry' => TimeEntry::class,
            'package' => Package::class,
            'sprint' => Sprint::class,
        ]);
    }

    private function registerPolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ReworkReason::class, ReworkReasonPolicy::class);
        Gate::policy(Sprint::class, SprintPolicy::class);
        Gate::policy(TimeEntry::class, TimeEntryPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskComment::class, TaskCommentPolicy::class);
    }
}
