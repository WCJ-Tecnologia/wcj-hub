<?php

namespace App\Platform\Models\Concerns;

use App\Platform\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * A record with history is deactivated, never soft deleted: the row keeps who
 * and when (`deactivated_at`, `deactivated_by`). Every "active" filter goes
 * through the `active` scope so no screen forgets it. See banco.md §4.
 */
trait Deactivatable
{
    /**
     * Whether anything already points to this record (sign-in, audit
     * authorship, children, links). Only a record without history may be
     * deleted for real.
     */
    abstract public function hasHistory(): bool;

    public function initializeDeactivatable(): void
    {
        $this->mergeCasts(['deactivated_at' => 'datetime']);

        // A new record starts active. Rows read from the database replace this.
        $this->attributes['deactivated_at'] ??= null;
    }

    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    /**
     * Fails closed: a row read without the column (partial select, schema
     * older than the column) never passes as active.
     */
    public function isActive(): bool
    {
        if (! array_key_exists('deactivated_at', $this->getAttributes())) {
            throw new LogicException(static::class.' was loaded without deactivated_at.');
        }

        return $this->deactivated_at === null;
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull($this->qualifyColumn('deactivated_at'));
    }

    /**
     * Stamps who and when on the way out and clears both on the way back.
     * A record already in the requested state keeps its original stamp.
     * Does not save.
     */
    public function setActive(bool $active, ?User $by): static
    {
        if ($active === $this->isActive()) {
            return $this;
        }

        return $this->forceFill($active
            ? ['deactivated_at' => null, 'deactivated_by' => null]
            : ['deactivated_at' => now(), 'deactivated_by' => $by?->getKey()]);
    }

    /**
     * Deletes for real only a record without history. The row stays locked
     * from the check to the delete, so no history can land in between.
     * Returns false, deleting nothing, when there is history.
     *
     * @param  (Closure(static): void)|null  $then  runs in the same transaction (audit, cleanup)
     */
    public function deleteIfUnused(?Closure $then = null): bool
    {
        return DB::transaction(function () use ($then) {
            $locked = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($locked->hasHistory()) {
                return false;
            }

            $locked->delete();

            if ($then !== null) {
                $then($locked);
            }

            return true;
        });
    }
}
