<?php

namespace Tests\Feature;

use App\Platform\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Support\CriaProjeto;
use Tests\TestCase;

/**
 * A busca por texto das listas tem teto de 200 caracteres, o mesmo do quadro e dos
 * indicadores; acima dele a busca é ignorada, como filtro inválido (B9, SEC-10).
 */
class BuscaTetoTest extends TestCase
{
    use CriaProjeto, RefreshDatabase;

    public function test_every_list_ignores_a_search_longer_than_200_characters(): void
    {
        $this->semearProjetos();
        $admin = User::factory()->admin()->create();
        $project = $this->projeto(['manager_id' => $admin->id]);

        $lists = [
            '/admin/users',
            '/admin/clients',
            '/admin/contacts',
            '/projetos',
            '/projetos/'.$project->id.'/sprints',
            '/minhas-horas',
        ];

        foreach ($lists as $path) {
            $this->actingAs($admin)
                ->get($path.'?search='.str_repeat('z', 201))
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->where('filters.search', ''));

            $this->actingAs($admin)
                ->get($path.'?search='.str_repeat('z', 200))
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->where('filters.search', str_repeat('z', 200)));
        }
    }
}
