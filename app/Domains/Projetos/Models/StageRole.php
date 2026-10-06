<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Domínio fechado (P11): o papel da etapa. Os ids são os da semente; board_stages.single_role_key
 * lê 1, 2 e 8 direto na coluna gerada.
 */
class StageRole extends Model
{
    public const QUEUE = 1;

    public const SPRINT = 2;

    public const DEVELOPMENT = 3;

    public const CODE_REVIEW = 4;

    public const TEST = 5;

    public const DONE = 6;

    public const PAUSED = 7;

    public const SUPPORT = 8;

    protected $fillable = ['slug', 'label'];
}
