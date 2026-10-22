<?php

namespace App\Domains\Projetos\Controllers;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Models\TaskAttachment;
use App\Domains\Projetos\Models\TaskComment;
use App\Domains\Projetos\Models\TaskPullRequest;
use App\Domains\Projetos\Requests\CardTextRequest;
use App\Domains\Projetos\Requests\ProposeHoursRequest;
use App\Domains\Projetos\Requests\StoreAttachmentsRequest;
use App\Domains\Projetos\Services\AttachmentService;
use App\Domains\Projetos\Services\CriterionService;
use App\Domains\Projetos\Services\TaskCommentService;
use App\Domains\Projetos\Services\TaskHoursService;
use App\Domains\Projetos\Services\TaskPullRequestService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * O que a ficha do card grava além dos campos (F3 §04 "Na ficha"; escopo B3, PR 2): horas
 * propostas e decididas (P27), critérios (P17, P37, T1, T9), links de PR (P23), comentários (P24) e
 * anexos (P24, P59). O filho da rota (critério, PR, comentário, anexo) tem de ser do card da rota:
 * de outro card é 404, antes da Policy, em toda rota (premissa 4 do PR; CR-1-04 da revisão).
 * Escritas autorizadas nos FormRequests ou aqui, antes de qualquer leitura.
 */
class TaskDetailController extends Controller
{
    public function __construct(
        private readonly TaskHoursService $hours,
        private readonly CriterionService $criteria,
        private readonly TaskPullRequestService $pullRequests,
        private readonly TaskCommentService $comments,
        private readonly AttachmentService $attachments,
    ) {}

    public function proposeHours(ProposeHoursRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(fn () => $this->hours->propose($card, $request->hours(), $request->user()), fn () => back());
    }

    public function approveHours(Request $request, Project $project, Task $card): RedirectResponse
    {
        $this->authorize('manageSprint', [Task::class, $project]);

        return $this->attempt(fn () => $this->hours->approveProposal($card, $request->user()), 'Reestimativa aprovada.');
    }

    public function rejectHours(Request $request, Project $project, Task $card): RedirectResponse
    {
        $this->authorize('manageSprint', [Task::class, $project]);

        return $this->attempt(fn () => $this->hours->rejectProposal($card, $request->user()), 'Reestimativa recusada.');
    }

    public function storeCriterion(CardTextRequest $request, Project $project, Task $card): RedirectResponse
    {
        $parent = $request->validated('parent_id');

        return $this->attempt(
            fn () => $this->criteria->add($card, $request->user(), $request->text('description'), $parent === null ? null : (int) $parent),
            fn () => back(),
        );
    }

    public function updateCriterion(CardTextRequest $request, Project $project, Task $card, TaskAcceptanceCriterion $acceptanceCriterion): RedirectResponse
    {
        $this->belongsToCard($acceptanceCriterion, $card);

        return $this->attempt(fn () => $this->criteria->update($acceptanceCriterion, $request->user(), $request->text('description')), fn () => back());
    }

    public function destroyCriterion(Request $request, Project $project, Task $card, TaskAcceptanceCriterion $acceptanceCriterion): RedirectResponse
    {
        $this->belongsToCard($acceptanceCriterion, $card);
        $this->authorize('update', $card);

        return $this->attempt(fn () => $this->criteria->delete($acceptanceCriterion, $request->user()), fn () => back());
    }

    public function storePullRequest(CardTextRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(fn () => $this->pullRequests->add($card, $request->user(), $request->text('url')), fn () => back());
    }

    public function destroyPullRequest(Request $request, Project $project, Task $card, TaskPullRequest $pullRequest): RedirectResponse
    {
        $this->belongsToCard($pullRequest, $card);
        $this->authorize('update', $card);

        return $this->attempt(fn () => $this->pullRequests->remove($pullRequest, $request->user()), fn () => back());
    }

    public function storeComment(CardTextRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(fn () => $this->comments->add($card, $request->user(), $request->text('body')), fn () => back());
    }

    public function updateComment(CardTextRequest $request, Project $project, Task $card, TaskComment $comment): RedirectResponse
    {
        $this->belongsToCard($comment, $card);

        return $this->attempt(fn () => $this->comments->update($comment, $request->user(), $request->text('body')), fn () => back());
    }

    public function destroyComment(Request $request, Project $project, Task $card, TaskComment $comment): RedirectResponse
    {
        $this->belongsToCard($comment, $card);
        $this->authorize('delete', $comment);

        return $this->attempt(fn () => $this->comments->delete($comment, $request->user()), fn () => back());
    }

    public function storeAttachments(StoreAttachmentsRequest $request, Project $project, Task $card): RedirectResponse
    {
        return $this->attempt(fn () => $this->attachments->upload($card, $request->user(), $request->uploads()), fn () => back());
    }

    public function destroyAttachment(Request $request, Project $project, Task $card, TaskAttachment $attachment): RedirectResponse
    {
        $this->belongsToCard($attachment, $card);
        $this->authorize('attach', $card);

        return $this->attempt(fn () => $this->attachments->remove($attachment, $request->user()), fn () => back());
    }

    /** Download pelo AttachmentService (B9, SEC-11): aqui só o card da rota e a Policy. */
    public function downloadAttachment(Request $request, Project $project, Task $card, TaskAttachment $attachment): StreamedResponse
    {
        $this->belongsToCard($attachment, $card);
        $this->authorize('view', $card);

        try {
            return $this->attachments->download($attachment);
        } catch (FileNotFoundException) {
            abort(404);
        }
    }

    private function belongsToCard(Model $child, Task $card): void
    {
        abort_unless((int) $child->getAttribute('task_id') === $card->id, 404);
    }
}
