<?php

namespace Tests\Feature;

use App\Platform\Models\Enterprise;
use App\Platform\Models\User;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseControllerTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_CNPJ = '11222333000181';

    public function test_admin_can_list_create_update_and_delete_enterprises(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/enterprises')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/enterprises/index')->has('enterprises.data', 0));

        $this->actingAs($admin)
            ->post('/admin/enterprises', ['name' => 'Empresa Exemplo Ltda', 'nickname' => 'Exemplo', 'document' => self::VALID_CNPJ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $enterprise = Enterprise::query()->where('nickname', 'Exemplo')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => Enterprise::class, 'auditable_id' => $enterprise->id, 'action' => 'created']);

        $this->actingAs($admin)
            ->get('/admin/enterprises')
            ->assertInertia(fn ($page) => $page->has('enterprises.data', 1)->where('enterprises.data.0.nickname', 'Exemplo'));

        $this->actingAs($admin)
            ->put('/admin/enterprises/'.$enterprise->id, ['name' => 'Empresa Exemplo Ltda', 'nickname' => 'Exemplo SP', 'active' => false])
            ->assertRedirect()
            ->assertSessionHas('success');

        $enterprise->refresh();
        $this->assertSame('Exemplo SP', $enterprise->nickname);
        $this->assertFalse($enterprise->isActive());
        $this->assertSame($admin->id, $enterprise->deactivated_by);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $enterprise->id, 'action' => 'updated']);

        $this->actingAs($admin)
            ->get('/admin/enterprises')
            ->assertInertia(fn ($page) => $page
                ->where('enterprises.data.0.active', false)
                ->where('enterprises.data.0.deactivated_by', $admin->name)
                ->where('enterprises.data.0.deactivated_at', LocalTime::format($enterprise->deactivated_at)));

        // Nothing points to it yet: deleting is allowed and it is for real.
        $this->actingAs($admin)
            ->delete('/admin/enterprises/'.$enterprise->id)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertModelMissing($enterprise);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $enterprise->id, 'action' => 'deleted']);
    }

    public function test_an_enterprise_with_users_or_branches_can_only_be_deactivated(): void
    {
        $admin = User::factory()->admin()->create();
        $withUser = Enterprise::factory()->create();
        User::factory()->financial()->create()->enterprises()->attach($withUser);
        $withBranch = Enterprise::factory()->create();
        Enterprise::factory()->create(['parent_enterprise_id' => $withBranch->id]);

        foreach ([$withUser, $withBranch] as $enterprise) {
            $this->actingAs($admin)
                ->delete('/admin/enterprises/'.$enterprise->id)
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertModelExists($enterprise);
        }

        $this->assertDatabaseCount('user_has_enterprises', 1);
    }

    public function test_a_deactivated_enterprise_leaves_the_financial_scope_until_reactivated(): void
    {
        $admin = User::factory()->admin()->create();
        $enterprise = Enterprise::factory()->create(['name' => 'Alfa Ltda', 'nickname' => 'Alfa']);
        $financial = User::factory()->financial()->create();
        $financial->enterprises()->attach($enterprise);
        $payload = ['name' => 'Alfa Ltda', 'nickname' => 'Alfa'];

        $this->actingAs($admin)->put('/admin/enterprises/'.$enterprise->id, [...$payload, 'active' => false]);

        $this->assertFalse($financial->fresh()->canAccessEnterprise($enterprise->id));
        $this->actingAs($financial)->get('/admin/enterprises')->assertInertia(fn ($page) => $page->has('enterprises.data', 0));
        $this->assertDatabaseHas('user_has_enterprises', ['user_id' => $financial->id, 'enterprise_id' => $enterprise->id]);

        $this->actingAs($admin)->put('/admin/enterprises/'.$enterprise->id, [...$payload, 'active' => true]);

        $this->assertTrue($financial->fresh()->canAccessEnterprise($enterprise->id));
    }

    public function test_who_deactivated_only_reaches_whoever_can_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $enterprise = Enterprise::factory()->create();
        $enterprise->setActive(false, $admin)->save();

        $this->actingAs($admin)
            ->get('/admin/enterprises')
            ->assertInertia(fn ($page) => $page->where('enterprises.data.0.deactivated_by', $admin->name));

        $this->actingAs(User::factory()->supervisor()->create())
            ->get('/admin/enterprises')
            ->assertInertia(fn ($page) => $page
                ->where('enterprises.data.0.active', false)
                ->missing('enterprises.data.0.deactivated_by')
                ->missing('enterprises.data.0.deactivated_at'));
    }

    public function test_an_inactive_enterprise_is_not_offered_as_scope(): void
    {
        Enterprise::factory()->create(['nickname' => 'Ativa']);
        Enterprise::factory()->inactive()->create(['nickname' => 'Inativa']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/users')
            ->assertInertia(fn ($page) => $page
                ->has('enterpriseOptions', 1)
                ->where('enterpriseOptions.0.nickname', 'Ativa'));
    }

    public function test_validation_rejects_missing_fields_and_duplicate_document(): void
    {
        $admin = User::factory()->admin()->create();
        Enterprise::factory()->create(['document' => self::VALID_CNPJ]);

        $this->actingAs($admin)
            ->from('/admin/enterprises')
            ->post('/admin/enterprises', ['name' => '', 'nickname' => '', 'document' => self::VALID_CNPJ])
            ->assertRedirect('/admin/enterprises')
            ->assertSessionHasErrors(['name', 'nickname', 'document']);
    }

    public function test_search_filters_by_name_nickname_or_document(): void
    {
        $admin = User::factory()->admin()->create();
        Enterprise::factory()->create(['name' => 'Alfa Comercio', 'nickname' => 'Alfa']);
        Enterprise::factory()->create(['name' => 'Beta Servicos', 'nickname' => 'Beta']);

        $this->actingAs($admin)
            ->get('/admin/enterprises?search=Beta')
            ->assertInertia(fn ($page) => $page->has('enterprises.data', 1)->where('enterprises.data.0.nickname', 'Beta'));
    }

    public function test_supervisor_and_financial_cannot_write_enterprises(): void
    {
        $enterprise = Enterprise::factory()->create();

        foreach ([User::factory()->supervisor()->create(), User::factory()->financial()->create()] as $user) {
            $this->actingAs($user)->post('/admin/enterprises', ['name' => 'Nova', 'nickname' => 'Nova'])->assertForbidden();
            $this->actingAs($user)->put('/admin/enterprises/'.$enterprise->id, ['name' => 'X', 'nickname' => 'X'])->assertForbidden();
            $this->actingAs($user)->delete('/admin/enterprises/'.$enterprise->id)->assertForbidden();
        }

        $this->assertModelExists($enterprise);
        $this->assertTrue($enterprise->fresh()->isActive());
    }

    public function test_financial_user_only_sees_enterprises_in_its_scope(): void
    {
        $allowed = Enterprise::factory()->create(['nickname' => 'Permitida']);
        Enterprise::factory()->create(['nickname' => 'Proibida']);

        $financial = User::factory()->financial()->create();
        $financial->enterprises()->attach($allowed);

        $this->actingAs($financial)
            ->get('/admin/enterprises')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('enterprises.data', 1)
                ->where('enterprises.data.0.id', $allowed->id));

        // The search cannot be used to escape the scope either.
        $this->actingAs($financial)
            ->get('/admin/enterprises?search=Proibida')
            ->assertInertia(fn ($page) => $page->has('enterprises.data', 0));
    }

    public function test_supervisor_sees_every_enterprise_but_only_reads(): void
    {
        Enterprise::factory()->count(3)->create();

        $this->actingAs(User::factory()->supervisor()->create())
            ->get('/admin/enterprises')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('enterprises.data', 3));
    }
}
