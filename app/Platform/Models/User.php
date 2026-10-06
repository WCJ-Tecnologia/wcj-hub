<?php

namespace App\Platform\Models;

use App\Mail\ResetPasswordMail;
use App\Platform\Enums\UserTypeEnum;
use App\Platform\Models\Concerns\Deactivatable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    // Fora de App\Models, o Laravel não acha a factory pelo nome (ADR-001).
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /** @use HasFactory<UserFactory> */
    use Deactivatable, HasFactory, Notifiable;

    protected $fillable = [
        'user_type_id',
        'name',
        'document',
        'phone',
        'email',
        'password',
        'must_change_password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'user_type_id' => 'integer',
        ];
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        if (! $this->email) {
            return;
        }

        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        Mail::to($this->email)->send(new ResetPasswordMail($this, $resetUrl));
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    public function enterprises(): BelongsToMany
    {
        return $this->belongsToMany(Enterprise::class, 'user_has_enterprises');
    }

    public function type(): UserTypeEnum
    {
        return UserTypeEnum::from($this->user_type_id);
    }

    public function isAdmin(): bool
    {
        return $this->type() === UserTypeEnum::ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->type() === UserTypeEnum::SUPERVISOR;
    }

    public function isFinancial(): bool
    {
        return $this->type() === UserTypeEnum::FINANCIAL;
    }

    public function isBackofficeUser(): bool
    {
        return in_array($this->type(), UserTypeEnum::backoffice(), true);
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0] ?: $this->name;
    }

    /**
     * Enterprise ids this user may read. Null means "every enterprise".
     *
     * @return list<int>|null
     */
    public function scopedEnterpriseIds(): ?array
    {
        if ($this->isAdmin() || $this->isSupervisor()) {
            return null;
        }

        // A deactivated enterprise leaves the scope; the link stays for reactivation.
        return $this->enterprises()->active()->pluck('enterprises.id')->all();
    }

    /**
     * A person who ever signed in or acted in the system can only be
     * deactivated. Deleting would also erase authorship in audit_logs.
     */
    public function hasHistory(): bool
    {
        return $this->last_login_at !== null
            || AuditLog::query()->where('user_id', $this->getKey())->exists()
            || self::query()->where('deactivated_by', $this->getKey())->exists()
            || Enterprise::query()->where('deactivated_by', $this->getKey())->exists();
    }

    public function canAccessEnterprise(int $enterpriseId): bool
    {
        $scope = $this->scopedEnterpriseIds();

        return $scope === null || in_array($enterpriseId, $scope, true);
    }
}
