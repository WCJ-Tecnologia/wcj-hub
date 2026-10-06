<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * UTC in the database, local time on the screen and in day filters, and no
 * column type that ends in 2038. See .ai/core/banco.md, section 3.
 */
class DateTimeConventionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Migrations a derived project already applied before adopting these rules.
     * They are immutable (banco.md §5); list them by file name, never edit them.
     *
     * @var list<string>
     */
    private const LEGACY_MIGRATIONS = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The expectations below are written in São Paulo time.
        config(['app.display_timezone' => 'America/Sao_Paulo']);
    }

    public function test_the_app_runs_in_utc_and_shows_local_time(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('24/09/2026 23:30', LocalTime::format(Carbon::parse('2026-09-25 02:30:00', 'UTC')));
    }

    public function test_the_audit_day_filter_uses_the_local_day(): void
    {
        $admin = User::factory()->admin()->create();

        // 23:30 in São Paulo is already the next day in UTC.
        $this->travelTo(Carbon::parse('2026-09-24 23:30:00', LocalTime::zone()));
        $late = AuditLog::query()->create(['user_id' => $admin->id, 'action' => 'updated', 'auditable_type' => User::class, 'auditable_id' => $admin->id]);
        $this->travelBack();

        $this->assertSame('2026-09-25 02:30:00', $late->getRawOriginal('created_at'));

        $this->actingAs($admin)
            ->get('/admin/audit-logs?from=2026-09-24&to=2026-09-24')
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.created_at', '24/09/2026 23:30:00'));

        $this->actingAs($admin)
            ->get('/admin/audit-logs?from=2026-09-25&to=2026-09-25')
            ->assertInertia(fn ($page) => $page->has('logs.data', 0));
    }

    public function test_migrations_use_no_type_that_ends_in_2038_nor_soft_delete(): void
    {
        $forbidden = [
            '/->timestamps?(Tz)?\(/' => 'datetimes() / dateTime()',
            '/->nullableTimestamps(Tz)?\(/' => 'datetimes()',
            '/->softDeletes(Tz|Datetime)?\(/' => 'deactivated_at/deactivated_by (banco.md §4)',
            "/'deleted_at'/" => 'deactivated_at/deactivated_by (banco.md §4)',
            // Unix time in a 32-bit column, signed or not.
            "/->(unsigned)?(Tiny|Small|Medium)?[Ii]nteger\('(\w+_at|expiration|last_activity)'/" => 'bigInteger() / unsignedBigInteger()',
        ];

        foreach (glob(database_path('migrations/*.php')) as $file) {
            if (in_array(basename($file), self::LEGACY_MIGRATIONS, true)) {
                continue;
            }

            $code = (string) file_get_contents($file);

            foreach ($forbidden as $pattern => $instead) {
                $this->assertDoesNotMatchRegularExpression($pattern, $code, basename($file).": use {$instead}");
            }
        }
    }

    public function test_no_model_uses_soft_deletes(): void
    {
        foreach (glob(app_path('Models/*.php')) as $file) {
            $this->assertStringNotContainsString('SoftDeletes', (string) file_get_contents($file), basename($file).': use the Deactivatable trait (banco.md §4)');
        }
    }
}
