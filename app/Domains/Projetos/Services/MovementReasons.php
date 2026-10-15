<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ReworkReason;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\ValidationType;
use Illuminate\Validation\ValidationException;

/**
 * O que um movimento pede além do destino (F3 §02, diálogos de pausa e de retrabalho): o motivo
 * da pausa (P15) e a origem e o motivo da volta (P39, P40). O movimento não é recusado por isso:
 * só pede os campos, como o diálogo. Falta de campo volta como erro do campo, com as chaves do
 * `MovePayload` do kit (`pause_reason`, `rework_origin_id`, `rework_reason_id`, `rework_reason_text`).
 */
class MovementReasons
{
    /** Etapas de onde a volta para sprint ou desenvolvimento é retrabalho (P39). */
    private const REVIEWED_ROLES = [StageRole::CODE_REVIEW, StageRole::TEST, StageRole::DONE];

    /** Destinos da volta que conta retrabalho (P39). */
    private const REWORK_TARGETS = [StageRole::SPRINT, StageRole::DEVELOPMENT];

    /** P39: a volta de code review, teste ou pronto para sprint ou desenvolvimento. */
    public function isReturn(BoardStage $from, BoardStage $to): bool
    {
        return in_array($from->stage_role_id, self::REVIEWED_ROLES, true)
            && in_array($to->stage_role_id, self::REWORK_TARGETS, true);
    }

    /**
     * Os campos do movimento: origem e motivo só na volta que é retrabalho (com "foi engano", nada),
     * o texto do "Outro" ou da pausa. Tudo nulo nos demais.
     *
     * @param  array{mistake?: bool, rework_origin_id?: ?int, rework_reason_id?: ?int, rework_reason_text?: ?string, pause_reason?: ?string}  $data
     * @return array{rework_origin_id: ?int, rework_reason_id: ?int, reason_text: ?string}
     */
    public function resolve(Project $project, BoardStage $from, BoardStage $to, array $data): array
    {
        if ($this->isReturn($from, $to) && ! ($data['mistake'] ?? false)) {
            return $this->rework($data);
        }

        return ['rework_origin_id' => null, 'rework_reason_id' => null, 'reason_text' => $this->pauseReason($project, $from, $to, $data)];
    }

    /**
     * Origem e motivo, obrigatórios juntos (o CHECK do banco também exige); o motivo é da lista
     * geral, ativo (G7); o "Outro" pede o texto (P40).
     *
     * @param  array<string, mixed>  $data
     * @return array{rework_origin_id: int, rework_reason_id: int, reason_text: ?string}
     */
    private function rework(array $data): array
    {
        $errors = [];
        $origin = $data['rework_origin_id'] ?? null;
        $reason = ($data['rework_reason_id'] ?? null) !== null
            ? ReworkReason::query()->active()->find($data['rework_reason_id'])
            : null;
        $text = trim((string) ($data['rework_reason_text'] ?? ''));

        if ($origin === null || ! ValidationType::query()->whereKey($origin)->exists()) {
            $errors['rework_origin_id'] = 'Escolha a origem.';
        }

        if ($reason === null) {
            $errors['rework_reason_id'] = 'Escolha o motivo.';
        } elseif ($reason->requires_text && $text === '') {
            $errors['rework_reason_text'] = 'Descreva o motivo.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return ['rework_origin_id' => (int) $origin, 'rework_reason_id' => $reason->id, 'reason_text' => $reason->requires_text ? $text : null];
    }

    /**
     * P15: o motivo é pedido ao entrar na pausa quando o projeto exige; com a exigência desligada,
     * fica o que vier. Andar de uma pausa para outra não pede de novo.
     *
     * @param  array<string, mixed>  $data
     */
    private function pauseReason(Project $project, BoardStage $from, BoardStage $to, array $data): ?string
    {
        if ($to->stage_role_id !== StageRole::PAUSED || $from->stage_role_id === StageRole::PAUSED) {
            return null;
        }

        $text = trim((string) ($data['pause_reason'] ?? ''));

        if ($text === '' && $project->require_pause_reason) {
            throw ValidationException::withMessages(['pause_reason' => 'Informe o motivo da pausa.']);
        }

        return $text === '' ? null : $text;
    }
}
