<?php

namespace App\Platform\Models;

use App\Mail\ResetPasswordMail;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Concerns\Deactivatable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

/**
 * Usuário interno (docs/banco.md, users). Sem document, phone, email_verified_at,
 * last_login_at nem remember_token (seção 9): o login fica em audit_logs.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Deactivatable, HasFactory, Notifiable;

    // Sem coluna remember_token (banco.md, seção 4): o "lembrar de mim" não existe.
    protected $rememberTokenName = '';

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'must_change_password',
        'daily_hours',
        'ai_write_allowed',
    ];

    protected $hidden = [
        'password',
    ];

    // Fora de App\Models, o Laravel não acha a factory pelo nome (ADR-001).
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'daily_hours' => 'decimal:2',
            'ai_write_allowed' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        Mail::to($this->email)->send(new ResetPasswordMail($this, $resetUrl));
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function aiAuthorizations(): HasMany
    {
        return $this->hasMany(AiAuthorization::class);
    }

    /**
     * "Último acesso" (F1 §03): o login mora na auditoria, não em coluna de users (seção 9).
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithLastLogin(Builder $query): void
    {
        $query->addSelect(['last_login_at' => AuditLog::query()
            ->selectRaw('max(created_at)')
            ->whereColumn('audit_logs.user_id', 'users.id')
            ->where('action', AuditActionEnum::LOGIN->value),
        ])->withCasts(['last_login_at' => 'datetime']);
    }

    public function type(): RoleEnum
    {
        return RoleEnum::from($this->role_id);
    }

    public function isAdmin(): bool
    {
        return $this->type() === RoleEnum::ADMIN;
    }

    public function isProjectManager(): bool
    {
        return $this->type() === RoleEnum::PROJECT_MANAGER;
    }

    public function isDeveloper(): bool
    {
        return $this->type() === RoleEnum::DEVELOPER;
    }

    public function isSales(): bool
    {
        return $this->type() === RoleEnum::SALES;
    }

    public function isBackofficeUser(): bool
    {
        return in_array($this->type(), RoleEnum::backoffice(), true);
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0] ?: $this->name;
    }

    /**
     * Quem já entrou ou agiu no sistema só se desativa (G2): o login e toda autoria estão em
     * audit_logs (seção 9), e qualquer outra FK que aponte para a pessoa (dev da tarefa,
     * alocação, horas, quem desativou) também conta.
     */
    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
