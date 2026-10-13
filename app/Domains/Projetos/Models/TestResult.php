<?php

namespace App\Domains\Projetos\Models;

use Illuminate\Database\Eloquent\Model;

/** Domínio fechado (P36, P37; docs/banco.md, test_results). */
class TestResult extends Model
{
    public const PENDING = 1;

    public const PASSED = 2;

    public const FAILED = 3;

    protected $fillable = ['slug', 'label'];
}
