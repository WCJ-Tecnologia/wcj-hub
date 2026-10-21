<?php

use App\Domains\Projetos\Controllers\BoardController;
use App\Domains\Projetos\Controllers\BoardStageController;
use App\Domains\Projetos\Controllers\IndicatorsController;
use App\Domains\Projetos\Controllers\ProjectAreaController;
use App\Domains\Projetos\Controllers\ProjectController;
use App\Domains\Projetos\Controllers\ProjectMemberController;
use App\Domains\Projetos\Controllers\ProjectSettingsController;
use App\Domains\Projetos\Controllers\ReworkReasonController;
use App\Domains\Projetos\Controllers\SprintController;
use App\Domains\Projetos\Controllers\SprintTaskController;
use App\Domains\Projetos\Controllers\TaskController;
use App\Domains\Projetos\Controllers\TaskDetailController;
use App\Domains\Projetos\Controllers\TaskHistoryController;
use App\Domains\Projetos\Controllers\TaskTypeController;
use App\Domains\Projetos\Controllers\TimeEntryController;
use App\Platform\Controllers\Admin\AuditLogController;
use App\Platform\Controllers\Admin\ClientController;
use App\Platform\Controllers\Admin\ContactController;
use App\Platform\Controllers\Admin\DashboardController;
use App\Platform\Controllers\Admin\ProfileController;
use App\Platform\Controllers\Admin\UserController;
use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use App\Platform\Controllers\Auth\ChangePasswordController;
use App\Platform\Controllers\Auth\ForgotPasswordController;
use App\Platform\Controllers\Auth\NewPasswordController;
use App\Platform\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'admin.dashboard' : 'login'));

/*
|--------------------------------------------------------------------------
| Public routes — the declared exception list
|--------------------------------------------------------------------------
| Every credential endpoint goes through the named "login" rate limiter.
*/

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('admin/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('admin/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:login')
        ->name('password.email');

    Route::get('admin/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('admin/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:login')
        ->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated — everything else
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('admin/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('backoffice')->group(function () {
        Route::get('admin/first-access', [ChangePasswordController::class, 'edit'])->name('password.change');
        Route::put('admin/first-access', [ChangePasswordController::class, 'update'])
            ->middleware('throttle:login')
            ->name('password.change.update');
    });
});

// O sino do shell (F3 §07): cada pessoa marca só os próprios avisos.
Route::prefix('avisos')
    ->name('notifications.')
    ->middleware(['auth', 'backoffice', 'password.current'])
    ->group(function () {
        Route::post('lidas', [NotificationController::class, 'readAll'])->name('read-all');
        Route::post('{notification}/lida', [NotificationController::class, 'read'])->whereUuid('notification')->name('read');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'backoffice', 'password.current'])
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');

        Route::resource('clients', ClientController::class)->except(['create', 'edit']);
        Route::post('clients/{client}/deactivate', [ClientController::class, 'deactivate'])->name('clients.deactivate');
        Route::post('clients/{client}/reactivate', [ClientController::class, 'reactivate'])->name('clients.reactivate');

        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::post('clients/{client}/contacts', [ContactController::class, 'store'])->name('clients.contacts.store');
        Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::post('contacts/{contact}/deactivate', [ContactController::class, 'deactivate'])->name('contacts.deactivate');
        Route::post('contacts/{contact}/reactivate', [ContactController::class, 'reactivate'])->name('contacts.reactivate');
        Route::post('contacts/{contact}/anonymize', [ContactController::class, 'anonymize'])->name('contacts.anonymize');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Listas gerais do módulo Projetos (escopo B2, /admin/listas): só o Administrador (G7).
        Route::get('listas', [ReworkReasonController::class, 'index'])->name('lists.index');
        Route::post('rework-reasons', [ReworkReasonController::class, 'store'])->name('rework-reasons.store');
        Route::put('rework-reasons/{rework_reason}', [ReworkReasonController::class, 'update'])->name('rework-reasons.update');
        Route::delete('rework-reasons/{rework_reason}', [ReworkReasonController::class, 'destroy'])->name('rework-reasons.destroy');
        Route::post('rework-reasons/{rework_reason}/deactivate', [ReworkReasonController::class, 'deactivate'])->name('rework-reasons.deactivate');
        Route::post('rework-reasons/{rework_reason}/reactivate', [ReworkReasonController::class, 'reactivate'])->name('rework-reasons.reactivate');
    });

/*
|--------------------------------------------------------------------------
| Projetos (escopo B2). Caminhos em português, como o escopo define.
|--------------------------------------------------------------------------
*/

Route::prefix('projetos')
    ->name('projects.')
    ->middleware(['auth', 'backoffice', 'password.current'])
    ->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::put('{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::post('{project}/encerrar', [ProjectController::class, 'finish'])->name('finish');
        Route::post('{project}/reabrir', [ProjectController::class, 'reopen'])->name('reopen');
        // "Novo cliente" dentro do formulário do projeto (N6): mesma Policy e Service de /admin/clients.
        Route::post('clientes', [ProjectController::class, 'storeClient'])->name('clients.store');

        // Configuração do projeto (F2: Pessoas, Etapas, Áreas, Padrões). O filho da rota é
        // resolvido pela relação do projeto (scopeBindings): etapa ou área de outro projeto é 404.
        Route::scopeBindings()->group(function () {
            Route::get('{project}/configuracao', [ProjectSettingsController::class, 'show'])->name('settings');
            Route::put('{project}/padroes', [ProjectSettingsController::class, 'updateDefaults'])->name('defaults.update');

            Route::post('{project}/pessoas', [ProjectMemberController::class, 'store'])->name('members.store');
            Route::delete('{project}/pessoas/{user}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');

            Route::post('{project}/etapas', [BoardStageController::class, 'store'])->name('stages.store');
            Route::put('{project}/etapas/ordem', [BoardStageController::class, 'reorder'])->name('stages.reorder');
            Route::put('{project}/etapas/{board_stage}', [BoardStageController::class, 'update'])->name('stages.update');
            Route::delete('{project}/etapas/{board_stage}', [BoardStageController::class, 'destroy'])->name('stages.destroy');

            Route::post('{project}/areas', [ProjectAreaController::class, 'store'])->name('areas.store');
            Route::put('{project}/areas/{area}', [ProjectAreaController::class, 'update'])->name('areas.update');
            Route::delete('{project}/areas/{area}', [ProjectAreaController::class, 'destroy'])->name('areas.destroy');
            Route::post('{project}/areas/{area}/deactivate', [ProjectAreaController::class, 'deactivate'])->name('areas.deactivate');
            Route::post('{project}/areas/{area}/reactivate', [ProjectAreaController::class, 'reactivate'])->name('areas.reactivate');

            // Sprints (F5, escopo B5). Sprint e tarefa de outro projeto são 404 (scopeBindings).
            Route::get('{project}/sprints', [SprintController::class, 'index'])->name('sprints.index');
            Route::post('{project}/sprints', [SprintController::class, 'store'])->name('sprints.store');
            Route::put('{project}/sprints/{sprint}', [SprintController::class, 'update'])->name('sprints.update');
            Route::delete('{project}/sprints/{sprint}', [SprintController::class, 'destroy'])->name('sprints.destroy');
            Route::post('{project}/sprints/{sprint}/encerrar', [SprintController::class, 'close'])->name('sprints.close');
            Route::post('{project}/sprints/{sprint}/reabrir', [SprintController::class, 'reopen'])->name('sprints.reopen');

            // Pôr e tirar card da sprint e horas previstas pelo gestor (P27, P45). O arrastar do
            // quadro (B3) chama os mesmos Services.
            Route::post('{project}/sprint-aberta/tarefas', [SprintTaskController::class, 'store'])->name('sprints.tasks.store');
            // Montar a sprint em lote pelo quadro (botão *Montar sprint*), criando a sprint se não há aberta (D-GER-35).
            Route::post('{project}/sprint-aberta/tarefas/lote', [SprintTaskController::class, 'storeMany'])->name('sprints.tasks.store-many');
            Route::delete('{project}/tarefas/{task}/sprint', [SprintTaskController::class, 'destroy'])->name('sprints.tasks.destroy');
            Route::put('{project}/tarefas/{task}/horas-previstas', [SprintTaskController::class, 'estimate'])->name('tasks.estimate');
            Route::post('{project}/tarefas/{task}/reestimativa/aprovar', [SprintTaskController::class, 'approve'])->name('tasks.proposal.approve');
            Route::post('{project}/tarefas/{task}/reestimativa/recusar', [SprintTaskController::class, 'reject'])->name('tasks.proposal.reject');

            // Lançar horas numa tarefa (P30): de Minhas horas e, no B3, da ficha do card.
            Route::post('{project}/tarefas/{task}/horas', [TimeEntryController::class, 'store'])->name('time-entries.store');

            // Quadro e card (F3, escopo B3). {card} é a chave "SGM-17", procurada só entre os cards
            // do projeto da rota (Route::bind no AppServiceProvider): de outro projeto é 404.
            Route::get('{project}/quadro', [BoardController::class, 'show'])->name('board');
            Route::post('{project}/cards', [TaskController::class, 'store'])->name('cards.store');
            Route::post('{project}/cards/suporte', [TaskController::class, 'storeSupport'])->name('cards.support');
            Route::get('{project}/cards/{card}', [TaskController::class, 'show'])->name('cards.show');
            Route::post('{project}/cards/{card}/mover', [TaskController::class, 'move'])->name('cards.move');
            Route::patch('{project}/cards/{card}', [TaskController::class, 'update'])->name('cards.update');
            Route::post('{project}/cards/{card}/participantes', [TaskController::class, 'participant'])->name('cards.participants');
            // Tirar do quadro (P26; B3, PR 3): arquivar com motivo, desarquivar, excluir o nunca usado.
            Route::post('{project}/cards/{card}/arquivar', [TaskController::class, 'archive'])->name('cards.archive');
            Route::post('{project}/cards/{card}/desarquivar', [TaskController::class, 'unarchive'])->name('cards.unarchive');
            Route::delete('{project}/cards/{card}', [TaskController::class, 'destroy'])->name('cards.destroy');

            // A ficha do card (F3 §04 "Na ficha"; B3, PR 2). O filho é do card da rota: de outro card, 404.
            Route::post('{project}/cards/{card}/horas/propor', [TaskDetailController::class, 'proposeHours'])->name('cards.hours.propose');
            Route::post('{project}/cards/{card}/horas/aprovar', [TaskDetailController::class, 'approveHours'])->name('cards.hours.approve');
            Route::post('{project}/cards/{card}/horas/recusar', [TaskDetailController::class, 'rejectHours'])->name('cards.hours.reject');
            Route::post('{project}/cards/{card}/criterios', [TaskDetailController::class, 'storeCriterion'])->name('cards.criteria.store');
            Route::patch('{project}/cards/{card}/criterios/{acceptanceCriterion}', [TaskDetailController::class, 'updateCriterion'])->name('cards.criteria.update');
            Route::delete('{project}/cards/{card}/criterios/{acceptanceCriterion}', [TaskDetailController::class, 'destroyCriterion'])->name('cards.criteria.destroy');
            Route::post('{project}/cards/{card}/prs', [TaskDetailController::class, 'storePullRequest'])->name('cards.prs.store');
            Route::delete('{project}/cards/{card}/prs/{pullRequest}', [TaskDetailController::class, 'destroyPullRequest'])->name('cards.prs.destroy');
            Route::post('{project}/cards/{card}/comentarios', [TaskDetailController::class, 'storeComment'])->name('cards.comments.store');
            Route::patch('{project}/cards/{card}/comentarios/{comment}', [TaskDetailController::class, 'updateComment'])->name('cards.comments.update');
            Route::delete('{project}/cards/{card}/comentarios/{comment}', [TaskDetailController::class, 'destroyComment'])->name('cards.comments.destroy');
            Route::post('{project}/cards/{card}/anexos', [TaskDetailController::class, 'storeAttachments'])->name('cards.attachments.store');
            Route::get('{project}/cards/{card}/anexos/{attachment}', [TaskDetailController::class, 'downloadAttachment'])->name('cards.attachments.show');
            Route::delete('{project}/cards/{card}/anexos/{attachment}', [TaskDetailController::class, 'destroyAttachment'])->name('cards.attachments.destroy');

            // Indicadores e histórico (F7, escopo B7): só leitura. A tela de indicadores é de quem vê o
            // projeto; as abas do gestor pedem viewIndicators (o dev recebe só a dele, P54).
            Route::get('{project}/indicadores', [IndicatorsController::class, 'show'])->name('indicators');
            Route::get('{project}/cards/{card}/historico', [TaskHistoryController::class, 'show'])->name('cards.history');

            // Tipos do projeto, criados e editados pela etiqueta do quadro (14/10); as mesmas quatro
            // portas da área (desativar, reativar, excluir o nunca usado), só quem gere.
            Route::post('{project}/tipos', [TaskTypeController::class, 'store'])->name('types.store');
            Route::put('{project}/tipos/{taskType}', [TaskTypeController::class, 'update'])->name('types.update');
            Route::delete('{project}/tipos/{taskType}', [TaskTypeController::class, 'destroy'])->name('types.destroy');
            Route::post('{project}/tipos/{taskType}/deactivate', [TaskTypeController::class, 'deactivate'])->name('types.deactivate');
            Route::post('{project}/tipos/{taskType}/reactivate', [TaskTypeController::class, 'reactivate'])->name('types.reactivate');
        });
    });

/*
|--------------------------------------------------------------------------
| Minhas horas (F5, escopo B5): corrigir e excluir lançamento pelo id.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'backoffice', 'password.current'])->group(function () {
    Route::get('minhas-horas', [TimeEntryController::class, 'index'])->name('time-entries.index');
    Route::put('horas/{time_entry}', [TimeEntryController::class, 'update'])->name('time-entries.update');
    Route::delete('horas/{time_entry}', [TimeEntryController::class, 'destroy'])->name('time-entries.destroy');
});
