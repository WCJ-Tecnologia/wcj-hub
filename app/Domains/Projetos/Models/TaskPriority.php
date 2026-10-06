<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;

/** Domínio fechado (P19; docs/banco.txt, task_priorities). */
class TaskPriority extends Model
{
    public const LOW = 1;

    public const NORMAL = 2;

    public const HIGH = 3;

    public const URGENT = 4;

    protected $fillable = ['slug', 'label'];
}
