<?php

namespace App\Domains\Projetos\Models;

use App\Platform\Models\Contact;
use App\Platform\Models\ContactChannel;
use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * O card (P13–P28, P34, P52; docs/banco.txt, tasks). number vem do contador do projeto (T4) e
 * a chave "SGM-17" se deduz (T10). "Tudo da tarefa é do mesmo projeto" é asserção no Service
 * (R15), sem FK composta.
 */
class Task extends Model
{
    protected $fillable = [
        'project_id',
        'number',
        'board_stage_id',
        'task_type_id',
        'project_area_id',
        'task_priority_id',
        'developer_id',
        'qa_id',
        'estimated_hours',
        'proposed_hours',
        'position',
        'title',
        'story_as',
        'story_want',
        'story_so_that',
        'description',
        'cross_tested_by',
        'cross_tested_at',
        'due_on',
        'sprint_id',
        'package_id',
        'origin_task_id',
        'requester_contact_id',
        'contact_channel_id',
        'requested_at',
        'archived_at',
        'archive_reason',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'estimated_hours' => 'decimal:2',
            'proposed_hours' => 'decimal:2',
            'position' => 'integer',
            'cross_tested_at' => 'datetime',
            'due_on' => 'date',
            'requested_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function boardStage(): BelongsTo
    {
        return $this->belongsTo(BoardStage::class);
    }

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    public function projectArea(): BelongsTo
    {
        return $this->belongsTo(ProjectArea::class);
    }

    public function taskPriority(): BelongsTo
    {
        return $this->belongsTo(TaskPriority::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    public function qa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qa_id');
    }

    public function crossTestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cross_tested_by');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function originTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origin_task_id');
    }

    public function productionBugs(): HasMany
    {
        return $this->hasMany(self::class, 'origin_task_id');
    }

    public function requesterContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'requester_contact_id');
    }

    public function contactChannel(): BelongsTo
    {
        return $this->belongsTo(ContactChannel::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(TaskStageMovement::class);
    }

    public function acceptanceCriteria(): HasMany
    {
        return $this->hasMany(TaskAcceptanceCriterion::class);
    }

    public function pullRequests(): HasMany
    {
        return $this->hasMany(TaskPullRequest::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }
}
