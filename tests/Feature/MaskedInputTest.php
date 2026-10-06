<?php

namespace Tests\Feature;

use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MaskedInputTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_CNPJ = '11.222.333/0001-81';

    private const VALID_CPF = '529.982.247-25';

    public function test_a_formatted_cnpj_is_stored_as_digits(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/enterprises', [
                'name' => 'Empresa Exemplo',
                'nickname' => 'Exemplo SP',
                'document' => self::VALID_CNPJ,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enterprises', [
            'nickname' => 'Exemplo SP',
            'document' => '11222333000181',
        ]);
    }

    public function test_an_invalid_cnpj_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/enterprises', [
                'name' => 'Empresa',
                'nickname' => 'Empresa',
                'document' => '11.111.111/1111-11',
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_an_empty_document_stays_null(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/enterprises', [
                'name' => 'Sem CNPJ',
                'nickname' => 'Sem CNPJ',
                'document' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enterprises', [
            'nickname' => 'Sem CNPJ',
            'document' => null,
        ]);
    }

    public function test_a_formatted_cpf_and_phone_are_stored_as_digits(): void
    {
        Mail::fake();

        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/users', [
                'user_type_id' => UserTypeEnum::SUPERVISOR->value,
                'name' => 'João da Silva',
                'email' => 'joao@example.com',
                'document' => self::VALID_CPF,
                'phone' => '(11) 98765-4321',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'João da Silva',
            'document' => '52998224725',
            'phone' => '11987654321',
        ]);
    }

    public function test_an_invalid_cpf_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/users', [
                'user_type_id' => UserTypeEnum::SUPERVISOR->value,
                'name' => 'Documento ruim',
                'email' => 'ruim@example.com',
                'document' => '123.456.789-00',
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_a_phone_without_area_code_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/users', [
                'user_type_id' => UserTypeEnum::SUPERVISOR->value,
                'name' => 'Telefone ruim',
                'email' => 'fone@example.com',
                'phone' => '98765-4321',
            ])
            ->assertSessionHasErrors('phone');
    }
}
