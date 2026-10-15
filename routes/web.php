<?php

use App\Domains\Projetos\Controllers\BoardStageController;
use App\Domains\Projetos\Controllers\ProjectAreaController;
use App\Domains\Projetos\Controllers\ProjectController;
use App\Domains\Projetos\Controllers\ProjectMemberController;
use App\Domains\Projetos\Controllers\ProjectSettingsController;
use App\Domains\Projetos\Controllers\ReworkReasonController;
use App\Domains\Projetos\Controllers\SprintController;
use App\Domains\Projetos\Controllers\SprintTaskController;
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
            Route::delete('{project}/tarefas/{task}/sprint', [SprintTaskController::class, 'destroy'])->name('sprints.tasks.destroy');
            Route::put('{project}/tarefas/{task}/horas-previstas', [SprintTaskController::class, 'estimate'])->name('tasks.estimate');
            Route::post('{project}/tarefas/{task}/reestimativa/aprovar', [SprintTaskController::class, 'approve'])->name('tasks.proposal.approve');
            Route::post('{project}/tarefas/{task}/reestimativa/recusar', [SprintTaskController::class, 'reject'])->name('tasks.proposal.reject');

            // Lançar horas numa tarefa (P30): de Minhas horas e, no B3, da ficha do card.
            Route::post('{project}/tarefas/{task}/horas', [TimeEntryController::class, 'store'])->name('time-entries.store');
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
