<?php

namespace App\Policies;

use App\Models\Enterprise;
use App\Models\User;

class EnterprisePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBackofficeUser();
    }

    public function view(User $user, Enterprise $enterprise): bool
    {
        return $user->isBackofficeUser() && $user->canAccessEnterprise($enterprise->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Enterprise $enterprise): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Enterprise $enterprise): bool
    {
        return $user->isAdmin();
    }
}
