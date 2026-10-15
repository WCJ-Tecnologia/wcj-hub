<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\ReworkReason;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\Concerns\RefusesDuplicates;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Listas gerais do módulo, editadas pelo Administrador (G7; escopo B2). Hoje só os motivos de
 * retrabalho (P40): os tipos de tarefa passaram a ser do projeto em 14/10 e entram com a
 * migração do B3. Mora em Projetos, não no Platform, pela regra A9 (ADR-001). O "Outro"
 * (slug other) existe sempre: não se desativa, não se exclui e não deixa de pedir texto.
 */
class ListService
{
    use RefusesDuplicates;

    private const LABEL_UNIQUE = 'rework_reasons_label_unique';

    public const OTHER_LOCKED = 'O motivo "Outro" existe sempre: renomeie, mas ele não se desativa nem se exclui.';

    public const OTHER_REQUIRES_TEXT = 'O motivo "Outro" sempre pede texto.';

    public const ALREADY_USED = 'Motivo já usado em alguma volta para desenvolvimento: desative em vez de excluir';

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Motivos para a tela (F2 §03): o "Outro" por último; desativados só com "Mostrar desativados".
     *
     * @return list<ReworkReason>
     */
    public function reworkReasons(bool $showInactive): array
    {
        return ReworkReason::query()
            ->with('deactivatedBy:id,name')
            ->when(! $showInactive, fn ($query) => $query->active())
            ->orderByRaw('slug = ? asc', [ReworkReason::OTHER])
            ->orderBy('label')
            ->get()
            ->all();
    }

    /** @param  array{label: string, requires_text: bool}  $data */
    public function createReworkReason(array $data): ReworkReason
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($data) {
            $this->refuseDuplicate($data['label'], null);
            $reason = ReworkReason::query()->create(['slug' => null, ...$data]);
            $this->audit->log(AuditActionEnum::CREATED, $reason, null, $this->snapshot($reason));

            return $reason;
        }), fn (UniqueConstraintViolationException $e) => $this->duplicateRefusal($e, $data['label']));
    }

    /** @param  array{label: string, requires_text: bool}  $data */
    public function updateReworkReason(ReworkReason $reason, array $data): ReworkReason
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($reason, $data) {
            $locked = ReworkReason::query()->lockForUpdate()->findOrFail($reason->id);
            if ($locked->slug === ReworkReason::OTHER && ! $data['requires_text']) {
                throw new DomainException(self::OTHER_REQUIRES_TEXT);
            }
            $this->refuseDuplicate($data['label'], $locked->id);

            $before = $this->snapshot($locked);
            $locked->fill($data)->save();
            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked));
            }

            return $locked;
        }), fn (UniqueConstraintViolationException $e) => $this->duplicateRefusal($e, $data['label']));
    }

    /** Sai da lista de escolha; os movimentos que já o usam continuam (P8). */
    public function deactivateReworkReason(ReworkReason $reason, User $actor): void
    {
        if ($reason->slug === ReworkReason::OTHER) {
            throw new DomainException(self::OTHER_LOCKED);
        }

        DB::transaction(fn () => $reason->setActiveLocked(false, $actor, function (ReworkReason $locked) use ($actor) {
            $this->audit->log(AuditActionEnum::DEACTIVATED, $locked, ['active' => true], ['active' => false], $actor->id);
        }));
    }

    public function reactivateReworkReason(ReworkReason $reason, User $actor): void
    {
        DB::transaction(fn () => $reason->setActiveLocked(true, $actor, function (ReworkReason $locked) use ($actor) {
            $this->audit->log(AuditActionEnum::REACTIVATED, $locked, ['active' => false], ['active' => true], $actor->id);
        }));
    }

    /** G2: só o motivo que nenhum movimento usa; nunca o "Outro". */
    public function deleteReworkReason(ReworkReason $reason, User $actor): void
    {
        if ($reason->slug === ReworkReason::OTHER) {
            throw new DomainException(self::OTHER_LOCKED);
        }

        $deleted = $reason->deleteIfUnused(function (ReworkReason $deleted) use ($actor) {
            $this->audit->log(AuditActionEnum::DELETED, $deleted, $this->snapshot($deleted), null, $actor->id);
        });

        if (! $deleted) {
            throw new DomainException(self::ALREADY_USED);
        }
    }

    private function refuseDuplicate(string $label, ?int $ignoreId): void
    {
        $existing = $this->duplicate($label, $ignoreId);
        if ($existing !== null) {
            throw ValidationException::withMessages(['label' => "Já existe o motivo {$existing}."]);
        }
    }

    private function duplicate(string $label, ?int $ignoreId): ?string
    {
        return ReworkReason::query()
            ->where('label', $label)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->value('label');
    }

    private function duplicateRefusal(UniqueConstraintViolationException $e, string $label): ?ValidationException
    {
        if (! str_contains($e->getMessage(), self::LABEL_UNIQUE)) {
            return null;
        }

        return ValidationException::withMessages(['label' => 'Já existe o motivo '.($this->duplicate($label, null) ?? $label).'.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(ReworkReason $reason): array
    {
        return $reason->only(['slug', 'label', 'requires_text']);
    }
}
