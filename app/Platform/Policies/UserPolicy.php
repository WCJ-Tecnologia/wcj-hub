<?php

namespace App\Platform\Policies;

use App\Platform\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->isAdmin() && $target->email !== null;
    }
}
