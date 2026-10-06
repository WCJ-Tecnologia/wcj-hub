<?php

namespace App\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/** Domínio fechado: por onde o contato aconteceu (P52; docs/banco.txt, contact_channels). */
class ContactChannel extends Model
{
    public const WHATSAPP = 1;

    public const PHONE = 2;

    public const EMAIL = 3;

    public const MEETING = 4;

    public const OTHER = 5;

    protected $fillable = ['slug', 'label'];
}
