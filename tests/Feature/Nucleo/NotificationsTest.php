<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Models\User;
use App\Platform\Services\NotificationPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * O sino do shell (spec b3-arquivar-e-avisos, AVI-01; F3 §07): cada pessoa vê e marca só os
 * próprios avisos. As props compartilhadas trazem os 10 mais recentes, não lidos primeiro; aviso
 * de outra pessoa é 404 e continua não lido.
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private User $bia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ana = User::factory()->developer()->create();
        $this->bia = User::factory()->developer()->create();
    }

    /** Um aviso como os de Projetos gravam (`text`, `url`, `key`), no instante dado (UTC). */
    private function aviso(User $user, string $text, string $at, ?string $readAt = null, ?string $url = '/projetos/1/cards/SGM-1'): DatabaseNotification
    {
        return $user->notifications()->forceCreate([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Domains\\Projetos\\Notifications\\TaskAssigned',
            'data' => ['text' => $text, 'url' => $url, 'key' => 'SGM-1'],
            'created_at' => $at,
            'updated_at' => $at,
            'read_at' => $readAt,
        ]);
    }

    /** @return array{unread_count: int, items: list<array<string, mixed>>} */
    private function sino(User $as): array
    {
        return $this->actingAs($as)->get('/projetos')->assertOk()->viewData('page')['props']['notifications'];
    }

    public function test_the_shared_props_carry_only_my_notices_unread_first_in_sao_paulo_time(): void
    {
        $this->aviso($this->ana, 'Lido e mais novo', '2026-10-20 15:00:00', readAt: '2026-10-20 15:05:00');
        $this->aviso($this->ana, 'Não lido antigo', '2026-10-18 13:00:00');
        $this->aviso($this->ana, 'Não lido novo', '2026-10-19 13:00:00', url: null);
        $this->aviso($this->bia, 'Da Bia', '2026-10-20 16:00:00');

        $sino = $this->sino($this->ana);

        $this->assertSame(2, $sino['unread_count']);
        $this->assertSame(['Não lido novo', 'Não lido antigo', 'Lido e mais novo'], array_column($sino['items'], 'text'));
        $this->assertSame(
            ['url' => null, 'created_at' => '2026-10-19 10:00', 'read_at' => null],
            collect($sino['items'][0])->only(['url', 'created_at', 'read_at'])->all(),
        );
        $this->assertSame(['/projetos/1/cards/SGM-1', '2026-10-20 12:05'], [$sino['items'][2]['url'], $sino['items'][2]['read_at']]);
    }

    public function test_the_bell_shows_the_ten_most_recent(): void
    {
        foreach (range(1, 12) as $i) {
            $this->aviso($this->ana, "Aviso {$i}", sprintf('2026-10-%02d 12:00:00', $i));
        }

        $sino = $this->sino($this->ana);

        $this->assertSame(12, $sino['unread_count']);
        $this->assertCount(NotificationPresenter::LIMIT, $sino['items']);
        $this->assertSame(['Aviso 12', 'Aviso 3'], [$sino['items'][0]['text'], $sino['items'][9]['text']]);
    }

    public function test_marking_one_as_read_touches_only_that_one(): void
    {
        $um = $this->aviso($this->ana, 'Um', '2026-10-19 12:00:00');
        $dois = $this->aviso($this->ana, 'Dois', '2026-10-19 13:00:00');

        $this->actingAs($this->ana)->from('/projetos')->post("/avisos/{$um->id}/lida")->assertRedirect('/projetos');

        $this->assertNotNull($um->fresh()->read_at);
        $this->assertNull($dois->fresh()->read_at);
    }

    public function test_a_notice_of_another_person_is_not_found_and_stays_unread(): void
    {
        $daBia = $this->aviso($this->bia, 'Da Bia', '2026-10-19 12:00:00');

        $this->actingAs($this->ana)->post("/avisos/{$daBia->id}/lida")->assertNotFound();
        $this->actingAs($this->ana)->post('/avisos/nao-e-uuid/lida')->assertNotFound();

        $this->assertNull($daBia->fresh()->read_at);
    }

    public function test_marking_all_as_read_touches_only_mine(): void
    {
        $meu = $this->aviso($this->ana, 'Meu', '2026-10-19 12:00:00');
        $lido = $this->aviso($this->ana, 'Já lido', '2026-10-18 12:00:00', readAt: '2026-10-18 13:00:00');
        $daBia = $this->aviso($this->bia, 'Da Bia', '2026-10-19 12:00:00');

        $this->actingAs($this->ana)->post('/avisos/lidas')->assertRedirect();

        $this->assertNotNull($meu->fresh()->read_at);
        $this->assertSame('2026-10-18 13:00:00', $lido->fresh()->read_at->format('Y-m-d H:i:s'), 'o já lido não muda');
        $this->assertNull($daBia->fresh()->read_at);
        $this->assertSame(0, $this->sino($this->ana)['unread_count']);
    }

    public function test_the_routes_need_login(): void
    {
        $aviso = $this->aviso($this->ana, 'Um', '2026-10-19 12:00:00');

        $this->post("/avisos/{$aviso->id}/lida")->assertRedirect('/admin/login');
        $this->post('/avisos/lidas')->assertRedirect('/admin/login');

        $this->assertNull($aviso->fresh()->read_at);
    }
}
