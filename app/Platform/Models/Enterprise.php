<?php

namespace App\Platform\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Database\Factories\EnterpriseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enterprise extends Model
{
    // Fora de App\Models, o Laravel não acha a factory pelo nome (ADR-001).
    protected static function newFactory(): EnterpriseFactory
    {
        return EnterpriseFactory::new();
    }

    use Deactivatable, HasFactory;

    protected $fillable = [
        'parent_enterprise_id',
        'name',
        'nickname',
        'document',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_enterprise_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_enterprise_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_has_enterprises');
    }

    /**
     * An enterprise that already scopes someone or has branches can only be
     * deactivated. A derived project adds here every table that points to it.
     */
    public function hasHistory(): bool
    {
        return $this->users()->exists() || $this->children()->exists();
    }
}
