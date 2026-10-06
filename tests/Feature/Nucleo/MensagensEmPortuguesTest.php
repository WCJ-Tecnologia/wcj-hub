<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B1 T25: o erro de campo das telas do Núcleo sai em português (Estados e textos AC 2; NUC-14):
 * e-mail inválido, campo obrigatório, tamanho máximo e número fora do limite, com o nome do
 * campo como a tela o chama.
 */
class MensagensEmPortuguesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_os_testes_rodam_em_pt_br_como_a_aplicacao(): void
    {
        $this->assertSame('pt_BR', app()->getLocale());
    }

    public function test_campo_obrigatorio_e_email_invalido_no_convite(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/users', ['role_id' => RoleEnum::PROJECT_MANAGER->value, 'name' => '', 'email' => 'maria-sem-arroba'])
            ->assertSessionHasErrors([
                'name' => 'O campo nome é obrigatório.',
                'email' => 'O campo e-mail precisa ser um e-mail válido.',
            ]);
    }

    public function test_numero_fora_do_limite_na_carga_diaria(): void
    {
        $data = ['role_id' => RoleEnum::DEVELOPER->value, 'name' => 'Davi Dev', 'email' => 'davi@wcj.test'];

        $this->actingAs($this->admin)->post('/admin/users', [...$data, 'daily_hours' => '25'])
            ->assertSessionHasErrors(['daily_hours' => 'O campo carga diária precisa ser menor ou igual a 24.']);
        $this->actingAs($this->admin)->post('/admin/users', [...$data, 'daily_hours' => '0'])
            ->assertSessionHasErrors(['daily_hours' => 'O campo carga diária precisa ser maior que 0.']);
        $this->actingAs($this->admin)->post('/admin/users', [...$data, 'daily_hours' => 'oito'])
            ->assertSessionHasErrors(['daily_hours' => 'O campo carga diária precisa ser um número.']);
    }

    public function test_tamanho_maximo_no_cliente_e_no_contato(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->admin)
            ->post('/admin/clients', ['name' => str_repeat('a', 161), 'country' => 'NL', 'document' => ''])
            ->assertSessionHasErrors(['name' => 'O campo nome pode ter no máximo 160 caracteres.']);
        $this->actingAs($this->admin)
            ->post("/admin/clients/{$client->id}/contacts", ['name' => 'Joana', 'email' => 'joana@', 'phone' => str_repeat('9', 21)])
            ->assertSessionHasErrors([
                'email' => 'O campo e-mail precisa ser um e-mail válido.',
                'phone' => 'O campo telefone pode ter no máximo 20 caracteres.',
            ]);
    }

    public function test_valor_fora_da_lista(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/clients', ['name' => 'Bouw BV', 'country' => 'XX'])
            ->assertSessionHasErrors(['country' => 'O valor escolhido em país não é válido.']);
    }
}
