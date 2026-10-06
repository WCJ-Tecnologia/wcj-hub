<?php

namespace App\Platform\Models;

use App\Platform\Models\Concerns\Deactivatable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Empresa atendida, inclusive a própria WCJ (N5, N7; docs/banco.txt, clients). */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use Deactivatable, HasFactory;

    protected $fillable = [
        'name',
        'country',
        'document',
    ];

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /** Contato, projeto ou qualquer FK apontando para o cliente (banco.txt, seção 0). */
    public function hasHistory(): bool
    {
        return $this->isReferenced();
    }
}
