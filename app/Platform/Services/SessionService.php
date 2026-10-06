<?php

namespace App\Platform\Services;

use App\Platform\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Changing a password must close every other session of the account, otherwise
 * a stolen cookie survives the reset. This deletes the rows directly in the
 * `sessions` table, which is the only driver the template ships with.
 */
class SessionService
{
    public function terminateOthers(User $user, ?string $keepSessionId = null): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::connection(config('session.connection'))
            ->table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->when($keepSessionId !== null, fn ($query) => $query->where('id', '!=', $keepSessionId))
            ->delete();
    }
}
