<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;

/** Domínio fechado: origens de retrabalho (P39, P43; docs/banco.txt, validation_types). */
class ValidationType extends Model
{
    public const CODE_REVIEW = 1;

    public const CROSS_TEST = 2;

    public const QA = 3;

    public const HOMOLOGATION = 4;

    protected $fillable = ['slug', 'label'];
}
