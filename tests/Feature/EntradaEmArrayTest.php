<?php

namespace Tests\Feature;

use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * Parâmetro que chega como array (`email[]=x`, `search[]=x`) não derruba a requisição com 500:
 * nas telas públicas vira erro de validação ou campo vazio, nas listas vira o filtro padrão
 * (revisão dirigida, achado 5; B9, SEC-23).
 */
class EntradaEmArrayTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.timebox_duration' => 0]);
        // O contador do limitador expira pelo relógio de parede (ver LoginThrottleTest).
        $this->freezeTime();
    }

    public function test_the_public_credential_forms_refuse_an_email_array_without_500(): void
    {
        $this->post('/admin/login', ['email' => ['x@exemplo.wcj.test'], 'password' => 'qualquer-1'])
            ->assertStatus(302)->assertSessionHasErrors('email');
        $this->post('/admin/forgot-password', ['email' => ['x@exemplo.wcj.test']])
            ->assertStatus(302)->assertSessionHasErrors('email');
        $this->post('/admin/reset-password', [
            'token' => 'abc', 'email' => ['x@exemplo.wcj.test'],
            'password' => 'senha-nova-123', 'password_confirmation' => 'senha-nova-123',
        ])->assertStatus(302)->assertSessionHasErrors('email');
    }

    public function test_the_reset_screen_with_an_email_array_shows_an_empty_email(): void
    {
        $this->get('/admin/reset-password/abc?email[]=x')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('email', '')->where('token', 'abc'));
    }

    public function test_the_lists_fall_back_to_the_default_filter_when_a_parameter_is_an_array(): void
    {
        $this->semearProjetos();
        $admin = User::factory()->admin()->create();
        $project = $this->projeto(['manager_id' => $admin->id]);

        foreach (['/admin/users', '/admin/clients', '/admin/contacts', '/projetos', "/projetos/{$project->id}/sprints", '/minhas-horas'] as $path) {
            $this->actingAs($admin)->get($path.'?search[]=x')
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->where('filters.search', ''));
        }

        $this->actingAs($admin)->get('/admin/clients?country[]=BR')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('filters.country', null));

        $default = $this->actingAs($admin)->get('/minhas-horas')->assertOk()->viewData('page')['props']['filters'];
        $this->actingAs($admin)->get('/minhas-horas?from[]=2026-01-01&to[]=2026-01-31')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('filters.from', $default['from'])->where('filters.to', $default['to']));
    }

    /** O 429 do redefinir também aguenta `email[]` (Verifier do B9, rodada 1: M32). */
    public function test_the_throttled_reset_with_an_email_array_answers_429_with_an_empty_email(): void
    {
        $attempt = ['token' => 'abc', 'email' => ['x@exemplo.wcj.test'], 'password' => 'senha-nova-123', 'password_confirmation' => 'senha-nova-123'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/reset-password', $attempt)->assertStatus(302);
        }

        $this->withHeaders(['X-Inertia' => 'true'])
            ->post('/admin/reset-password', $attempt)
            ->assertStatus(429)
            ->assertJsonPath('component', 'auth/reset-password')
            ->assertJsonPath('props.email', '');
    }
}
