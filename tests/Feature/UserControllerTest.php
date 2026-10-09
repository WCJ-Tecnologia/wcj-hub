<?php

namespace Tests\Feature;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_admin_can_list_create_update_and_delete_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/index')->has('users.data', 1)->has('roles', 4));

        $this->actingAs($admin)
            ->post('/admin/users', [
                'role_id' => RoleEnum::PROJECT_MANAGER->value,
                'name' => 'Maria Souza',
                'email' => 'maria@example.com',
                'daily_hours' => '7.5',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $maria = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame('7.50', $maria->daily_hours);
        $this->assertTrue($maria->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'user', 'auditable_id' => $maria->id, 'action' => 'created']);

        $this->actingAs($admin)
            ->put('/admin/users/'.$maria->id, [
                'role_id' => RoleEnum::PROJECT_MANAGER->value,
                'name' => 'Maria S. Lima',
                'email' => 'maria@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Desativar é ação própria (D3), não mais a caixa "ativo" do formulário.
        $this->actingAs($admin)
            ->post('/admin/users/'.$maria->id.'/deactivate')
            ->assertRedirect()
            ->assertSessionHas('success');

        $maria->refresh();
        $this->assertSame('Maria S. Lima', $maria->name);
        $this->assertFalse($maria->isActive());
        $this->assertNotNull($maria->deactivated_at);
        $this->assertSame($admin->id, $maria->deactivated_by);

        // Maria never signed in: deleting is allowed and it is for real.
        $this->actingAs($admin)
            ->delete('/admin/users/'.$maria->id)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertModelMissing($maria);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $maria->id, 'action' => 'deleted']);
    }

    public function test_deactivation_keeps_who_and_when_and_reactivation_clears_both(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
        $target = User::factory()->projectManager()->create();
        $payload = ['role_id' => RoleEnum::PROJECT_MANAGER->value, 'name' => $target->name, 'email' => $target->email];

        // Desativar e reativar são rotas próprias (D3), não mais a caixa "ativo" do formulário.
        $this->actingAs($admin)->post('/admin/users/'.$target->id.'/deactivate')->assertSessionHas('success');
        $stamp = $target->fresh()->deactivated_at;

        // Desativado só aparece com o filtro "Mostrar desativados" (F1 §03).
        $this->actingAs($admin)
            ->get('/admin/users?show_inactive=1&search='.$target->email)
            ->assertInertia(fn ($page) => $page
                ->where('users.data.0.active', false)
                ->where('users.data.0.deactivated_by', 'Ana Admin')
                ->where('users.data.0.deactivated_at', LocalTime::format($stamp)));

        // Deactivating again, or saving the form, keeps the original stamp.
        $this->travel(1)->hour();
        $this->actingAs($admin)->post('/admin/users/'.$target->id.'/deactivate');
        $this->actingAs($admin)->put('/admin/users/'.$target->id, $payload);
        $this->assertEquals($stamp, $target->fresh()->deactivated_at);

        $this->actingAs($admin)->post('/admin/users/'.$target->id.'/reactivate')->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deactivated_at' => null, 'deactivated_by' => null]);

        // The way back is in the audit trail.
        $last = AuditLog::query()->where('auditable_id', $target->id)->where('action', 'reactivated')->latest('id')->firstOrFail();
        $this->assertSame(['active' => [false, true]], $last->changes);
    }

    public function test_is_active_fails_closed_when_the_column_was_not_loaded(): void
    {
        $user = User::factory()->inactive()->create();

        $this->expectException(\LogicException::class);
        User::query()->select(['id', 'email'])->findOrFail($user->id)->isActive();
    }

    public function test_deleting_a_user_never_signed_in_also_drops_the_pending_reset_token(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->projectManager()->create();
        Password::broker()->createToken($target);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $target->email]);

        $this->actingAs($admin)->delete('/admin/users/'.$target->id)->assertSessionHas('success');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $target->email]);
    }

    public function test_a_user_with_history_can_only_be_deactivated(): void
    {
        $admin = User::factory()->admin()->create();
        // The sign-in lives in audit_logs (banco.txt, seção 9), not in a users column.
        $signedIn = User::factory()->projectManager()->create();
        AuditLog::query()->create(['user_id' => $signedIn->id, 'action' => 'login', 'outcome' => 'success', 'auditable_type' => 'user', 'auditable_id' => $signedIn->id]);
        $author = User::factory()->projectManager()->create();
        AuditLog::query()->create(['user_id' => $author->id, 'action' => 'updated', 'outcome' => 'success', 'auditable_type' => 'user', 'auditable_id' => $admin->id]);
        // Deactivated someone outside the screens (command, job): no audit row, still history.
        $deactivator = User::factory()->admin()->create();
        User::factory()->projectManager()->create()->setActive(false, $deactivator)->save();

        foreach ([$signedIn, $author, $deactivator] as $user) {
            $this->actingAs($admin)
                ->delete('/admin/users/'.$user->id)
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertModelExists($user);
        }
    }

    public function test_email_is_required_and_unique(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', ['role_id' => 2, 'name' => 'Sem e-mail'])
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->from('/admin/users')
            ->post('/admin/users', ['role_id' => 2, 'name' => 'Duplicado', 'email' => 'admin@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_project_manager_cannot_list_nor_write_users(): void
    {
        // Usuários: só o Administrador (escopo, B1).
        $supervisor = User::factory()->projectManager()->create();
        $target = User::factory()->developer()->create();

        $this->actingAs($supervisor)->get('/admin/users')->assertForbidden();
        $this->actingAs($supervisor)->post('/admin/users', ['role_id' => 2, 'name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
        $this->actingAs($supervisor)->put('/admin/users/'.$target->id, ['role_id' => 2, 'name' => 'X', 'email' => $target->email])->assertForbidden();
        $this->actingAs($supervisor)->delete('/admin/users/'.$target->id)->assertForbidden();
    }

    public function test_developer_cannot_even_list_users(): void
    {
        $this->actingAs(User::factory()->developer()->create())->get('/admin/users')->assertForbidden();
    }

    public function test_admin_cannot_delete_itself(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)->delete('/admin/users/'.$admin->id)->assertForbidden();
    }

    public function test_the_last_active_admin_cannot_be_demoted_deactivated_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        // Two admins: demoting one is fine.
        $this->actingAs($admin)
            ->put('/admin/users/'.$other->id, ['role_id' => RoleEnum::PROJECT_MANAGER->value, 'name' => $other->name, 'email' => $other->email])
            ->assertSessionHas('success');

        // Now $admin is the last one.
        $this->actingAs($other->fresh())
            ->post('/admin/users/'.$admin->id.'/deactivate')
            ->assertForbidden();
        $this->actingAs($admin)
            ->post('/admin/users/'.$admin->id.'/deactivate')
            ->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isActive());

        $lastAdmin = User::factory()->admin()->create();
        $admin->fresh()->setActive(false, null)->save();

        $this->actingAs($lastAdmin)
            ->put('/admin/users/'.$lastAdmin->id, ['role_id' => RoleEnum::PROJECT_MANAGER->value, 'name' => $lastAdmin->name, 'email' => $lastAdmin->email])
            ->assertSessionHas('error');

        $this->assertTrue($lastAdmin->fresh()->isAdmin());
    }

    public function test_role_never_comes_from_a_self_service_path(): void
    {
        // The profile endpoint ignores role_id and active entirely.
        $supervisor = User::factory()->projectManager()->create();

        $this->actingAs($supervisor)
            ->put('/admin/profile', ['name' => 'Novo Nome', 'role_id' => RoleEnum::ADMIN->value, 'active' => true])
            ->assertSessionHas('success');

        $this->assertTrue($supervisor->fresh()->isProjectManager());
    }
}
