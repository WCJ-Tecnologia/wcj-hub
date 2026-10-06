<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Horas realizadas, por dia, em decimais (P30; docs/banco.txt, time_entries). */
class TimeEntry extends Model
{
    protected $fillable = ['task_id', 'user_id', 'worked_on', 'hours'];

    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
