<?php

namespace App\Platform\Services;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\AiAssistant;
use App\Platform\Models\AiAuthorization;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Support\Like;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * A tela de auditoria (G3; F1 §03, §05): filtros, páginas de 50 do mais recente para o mais
 * antigo e a linha já legível ("Usuário Maria", canal "IA: Claude", campo "Papel"). Só lê.
 */
class AuditLogQuery
{
    public const PER_PAGE = 50;

    /** Período padrão (F1 §05): hoje e os seis dias antes, no fuso local. */
    public const DEFAULT_DAYS = 7;

    /** O canal da tela: ai_authorization_id nulo (IA11). Os outros são os slugs dos assistentes. */
    public const SCREEN = 'screen';

    /** Os registros do Núcleo que têm nome; os dos módulos se identificam pelo tipo e o id (A9). */
    private const NAMED = ['user' => User::class, 'client' => Client::class, 'contact' => Contact::class];

    public function __construct(private readonly AuditLogPresenter $presenter) {}

    /**
     * Os filtros da query string, com o padrão de 7 dias. Valor inválido cai fora em vez de
     * recusar a página; o "from" inválido volta ao padrão (A8 da review de 07/10). *Histórico*
     * (tipo + id) não tem limite de período (F1 §01); "from" presente e vazio também não (a
     * requisição o entrega nulo).
     *
     * @param  array<string, mixed>  $input
     * @return array{search: string, from: string|null, to: string|null, user_id: int|null, channel: string|null, action: string|null, type: string|null, id: int|null, outcome: string|null}
     */
    public function filters(array $input): array
    {
        $valid = Validator::make($input, [
            'search' => ['string', 'max:120'],
            'from' => ['date_format:Y-m-d'],
            'to' => ['date_format:Y-m-d'],
            'user_id' => ['integer'],
            'channel' => ['in:'.implode(',', array_column($this->channelOptions(), 'value'))],
            'action' => ['in:'.implode(',', array_column(AuditActionEnum::cases(), 'value'))],
            'type' => ['string', 'max:120'],
            'id' => ['integer'],
            'outcome' => ['in:'.AuditLog::OUTCOME_SUCCESS.','.AuditLog::OUTCOME_DENIED],
        ])->valid();

        $type = $valid['type'] ?? null;
        $id = $type !== null && isset($valid['id']) ? (int) $valid['id'] : null;
        $defaultFrom = CarbonImmutable::now(LocalTime::zone())->subDays(self::DEFAULT_DAYS - 1)->toDateString();
        // O Validator deixa passar o vazio; aqui ele é "sem período", não uma data.
        $from = ($valid['from'] ?? '') === '' ? null : $valid['from'];
        $noPeriod = $id !== null || (array_key_exists('from', $input) && in_array($input['from'], [null, ''], true));

        return [
            'search' => trim((string) ($valid['search'] ?? '')),
            'from' => $from ?? ($noPeriod ? null : $defaultFrom),
            'to' => $valid['to'] ?? null,
            'user_id' => isset($valid['user_id']) ? (int) $valid['user_id'] : null,
            'channel' => $valid['channel'] ?? null,
            'action' => $valid['action'] ?? null,
            'type' => $type,
            'id' => $id,
            'outcome' => $valid['outcome'] ?? null,
        ];
    }

    /**
     * @param  array{search: string, from: string|null, to: string|null, user_id: int|null, channel: string|null, action: string|null, type: string|null, id: int|null, outcome: string|null}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $page = AuditLog::query()
            ->with(['user:id,name,email', 'aiAuthorization.aiAssistant:id,label'])
            ->when($filters['from'] !== null, fn ($q) => $q->where('created_at', '>=', LocalTime::startOfDay($filters['from'])))
            ->when($filters['to'] !== null, fn ($q) => $q->where('created_at', '<=', LocalTime::endOfDay($filters['to'])))
            ->when($filters['user_id'] !== null, fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['channel'] !== null, fn ($q) => $this->whereChannel($q, $filters['channel']))
            ->when($filters['action'] !== null, fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['type'] !== null, fn ($q) => $q->where('auditable_type', $filters['type']))
            ->when($filters['id'] !== null, fn ($q) => $q->where('auditable_id', $filters['id']))
            ->when($filters['outcome'] !== null, fn ($q) => $q->where('outcome', $filters['outcome']))
            ->when($filters['search'] !== '', fn ($q) => $this->whereRecordMatches($q, $filters['search']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        $names = $this->names($page->getCollection());

        return $page->through(fn (AuditLog $log) => $this->presenter->row($log, $names));
    }

    /**
     * As opções dos filtros (F1 §05).
     *
     * @return array<string, list<array{value: string|int, label: string}>>
     */
    public function options(): array
    {
        return [
            'users' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])->all(),
            'channels' => $this->channelOptions(),
            'actions' => array_map(fn (AuditActionEnum $action) => ['value' => $action->value, 'label' => $action->label()], AuditActionEnum::cases()),
            'types' => $this->presenter->typeOptions(),
            'outcomes' => [
                ['value' => AuditLog::OUTCOME_SUCCESS, 'label' => 'Ok'],
                ['value' => AuditLog::OUTCOME_DENIED, 'label' => 'Negado'],
            ],
        ];
    }

    /** "Usuário Maria" para o título da página do *Histórico*; tipo e id quando não há nome. */
    public function recordLabel(string $type, ?int $id): string
    {
        $names = $id === null ? [] : $this->namesOf([$type => [$id]]);

        return $this->presenter->record($type, $id, $names);
    }

    /** @return list<array{value: string, label: string}> */
    private function channelOptions(): array
    {
        return [
            ['value' => self::SCREEN, 'label' => 'Tela'],
            ...AiAssistant::query()->orderBy('id')->get(['slug', 'label'])
                ->map(fn (AiAssistant $assistant) => ['value' => $assistant->slug, 'label' => "IA: {$assistant->label}"])->all(),
        ];
    }

    /** @param  Builder<AuditLog>  $query */
    private function whereChannel(Builder $query, string $channel): void
    {
        if ($channel === self::SCREEN) {
            $query->whereNull('ai_authorization_id');

            return;
        }

        $query->whereIn('ai_authorization_id', AiAuthorization::query()->select('id')
            ->whereIn('ai_assistant_id', AiAssistant::query()->select('id')->where('slug', $channel)));
    }

    /**
     * A busca pela identificação do registro (F1 §05): o nome atual do usuário, do cliente ou do
     * contato, e o CNPJ do cliente. "SGM-17" (card) entra com o B3, pelo domínio de Projetos.
     *
     * @param  Builder<AuditLog>  $query
     */
    private function whereRecordMatches(Builder $query, string $search): void
    {
        $like = Like::contains($search);
        $document = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $search) ?? '');

        $query->where(fn (Builder $any) => $any
            ->orWhere(fn (Builder $q) => $q->where('auditable_type', 'user')
                ->whereIn('auditable_id', User::query()->select('id')->where('name', 'like', $like)))
            ->orWhere(fn (Builder $q) => $q->where('auditable_type', 'client')
                ->whereIn('auditable_id', Client::query()->select('id')->where(fn (Builder $c) => $c
                    ->where('name', 'like', $like)
                    ->when($document !== '', fn (Builder $doc) => $doc->orWhere('document', 'like', Like::contains($document))))))
            ->orWhere(fn (Builder $q) => $q->where('auditable_type', 'contact')
                ->whereIn('auditable_id', Contact::query()->select('id')->where('name', 'like', $like))));
    }

    /**
     * Os nomes atuais dos registros da página e dos clientes citados em changes, numa consulta
     * por tipo. O contato anonimizado já tem o nome trocado no banco (N12).
     *
     * @param  Collection<int, AuditLog>  $logs
     * @return array<string, array<int, string>>
     */
    private function names(Collection $logs): array
    {
        $ids = [];
        foreach ($logs as $log) {
            if ($log->auditable_id !== null && isset(self::NAMED[$log->auditable_type])) {
                $ids[$log->auditable_type][] = $log->auditable_id;
            }
            foreach ($log->changes['client_id'] ?? [] as $clientId) {
                if (is_int($clientId)) {
                    $ids['client'][] = $clientId;
                }
            }
        }

        return $this->namesOf($ids);
    }

    /**
     * @param  array<string, list<int>>  $ids
     * @return array<string, array<int, string>>
     */
    private function namesOf(array $ids): array
    {
        $names = [];
        foreach ($ids as $type => $list) {
            $model = self::NAMED[$type] ?? null;
            $names[$type] = $model === null ? [] : $model::query()->whereKey(array_unique($list))->pluck('name', 'id')->all();
        }

        return $names;
    }
}
