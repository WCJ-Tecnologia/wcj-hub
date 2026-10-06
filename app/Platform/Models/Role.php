<?php

namespace App\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Domínio fechado (N2): semeado, sem tela. Ver RoleEnum. */
class Role extends Model
{
    protected $fillable = ['slug', 'label'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
