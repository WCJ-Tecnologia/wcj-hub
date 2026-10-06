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
use App\Domains\Projetos\Services\ProjectClientProjects;
use App\Http\Responses\ThrottledAuthResponse;
use App\Platform\Contracts\ClientProjects;
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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Passport entra no B0 só com as tabelas (docs/banco.txt, seção 4). As rotas /oauth/*
        // ficam desligadas até o B8 (conexões de IA), que as liga com os escopos, o limitador
        // do /oauth/token (B9) e a tela de autorização.
        Passport::ignoreRoutes();

        // O Núcleo não conhece módulo (A9): pergunta pelo contrato, e Projetos responde (ADR-003).
        $this->app->bind(ClientProjects::class, ProjectClientProjects::class);
    }

    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerPolicies();
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
     * Alias de cada Model em audit_logs.auditable_type e notifications.notifiable_type
     * (docs/banco.txt, audit_logs; ADR-001). enforce: Model fora do mapa falha em vez de
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
    }
}
