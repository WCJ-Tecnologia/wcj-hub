<?php

namespace App\Platform\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pessoa de um cliente (N8) e "quem pediu" no card de suporte (P52; docs/banco.txt, contacts). */
class Contact extends Model
{
    use Deactivatable;

    protected $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
