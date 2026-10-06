<?php

namespace App\Platform\Services;

use App\Platform\Models\Enterprise;
use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Central place where the Financial role gets restricted to its own enterprises.
 * Every listing query of an enterprise-owned resource must go through here so a
 * hand-typed URL cannot leak data. Add one method per scoped resource.
 */
class Scoping
{
    /**
     * @param  Builder<Enterprise>  $query
     * @return Builder<Enterprise>
     */
    public function enterprises(Builder $query, User $user): Builder
    {
        $ids = $user->scopedEnterpriseIds();

        return $ids === null ? $query : $query->whereIn('enterprises.id', $ids);
    }

    /**
     * Enterprise options this user may filter by.
     *
     * @return Collection<int, array{id: int, name: string, nickname: string}>
     */
    public function enterpriseOptions(User $user)
    {
        return $this->enterprises(Enterprise::query()->active(), $user)
            ->orderBy('nickname')
            ->get(['id', 'name', 'nickname'])
            ->map(fn (Enterprise $enterprise) => [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                'nickname' => $enterprise->nickname,
            ]);
    }
}
