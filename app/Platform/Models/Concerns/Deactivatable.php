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

    /**
     * Foreign keys that point to each table, read once from the database catalog.
     *
     * @var array<string, list<array{table: string, column: string}>>
     */
    private static array $referencesCache = [];

    /**
     * "Já usado" (docs/banco.txt, seção 0): some foreign key points to this row. Read from the
     * catalog, so a table added later is covered without touching the model. A self-reference
     * from the row to itself (deactivated_by = own id) does not count.
     *
     * @param  list<string>  $ignore  "table.column" entries that do not count as history
     */
    protected function isReferenced(array $ignore = []): bool
    {
        foreach (self::foreignKeysPointingTo($this->getTable()) as $reference) {
            if (in_array($reference['table'].'.'.$reference['column'], $ignore, true)) {
                continue;
            }

            $query = DB::table($reference['table'])->where($reference['column'], $this->getKey());

            if ($reference['table'] === $this->getTable()) {
                $query->where($this->getKeyName(), '!=', $this->getKey());
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{table: string, column: string}>
     */
    private static function foreignKeysPointingTo(string $table): array
    {
        return self::$referencesCache[$table] ??= DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('REFERENCED_TABLE_NAME', $table)
            ->orderBy('TABLE_NAME')
            ->orderBy('COLUMN_NAME')
            ->get(['TABLE_NAME as table', 'COLUMN_NAME as column'])
            ->map(fn (object $row) => ['table' => (string) $row->table, 'column' => (string) $row->column])
            ->all();
    }

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
     * Activates or deactivates by the locked row, never by the instance in memory: two clicks or
     * two people write one change, and an old instance does not record a change that did not
     * happen (CR-1-15, CR-2-06, CR-3-01). Opens its own transaction, like deleteIfUnused (a
     * savepoint inside the caller's), so the lock always lasts until the commit and the save and
     * the audit never split in two (CR-5-02). Returns false, writing nothing, when the row is
     * already in that state.
     *
     * @param  (Closure(static): void)|null  $then  runs with the locked row after the save (audit, side effects)
     */
    public function setActiveLocked(bool $active, User $actor, ?Closure $then = null): bool
    {
        return DB::transaction(function () use ($active, $actor, $then) {
            $locked = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($locked->isActive() === $active) {
                return false;
            }

            $locked->setActive($active, $actor)->save();

            if ($then !== null) {
                $then($locked);
            }

            return true;
        });
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
