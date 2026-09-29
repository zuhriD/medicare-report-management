<?php

namespace App\Policies;

use App\Models\AllowancePeriod;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AllowancePeriodPolicy
{
    use HandlesAuthorization;

    private function hasHrOrAdminRole(User $user): bool
    {
        return $user->hasRole(['admin', 'super_admin', 'hr', 'HR', 'Admin', 'Super Admin', 'lead', 'Lead']);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasHrOrAdminRole($user) || $user->can('view_any_allowance::period');
    }

    public function view(User $user, AllowancePeriod $allowancePeriod): bool
    {
        return $this->hasHrOrAdminRole($user) || $user->can('view_allowance::period');
    }

    public function create(User $user): bool
    {
        return $this->hasHrOrAdminRole($user) || $user->can('create_allowance::period');
    }

    public function update(User $user, AllowancePeriod $allowancePeriod): bool
    {
        return $this->hasHrOrAdminRole($user) || $user->can('update_allowance::period');
    }

    public function delete(User $user, AllowancePeriod $allowancePeriod): bool
    {
        // Don't allow deletion if any staff already paid
        if ($allowancePeriod->periodStaff()->where('payment_status', 'paid')->exists()) {
            return false;
        }

        return $this->hasHrOrAdminRole($user) || $user->can('delete_allowance::period');
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasHrOrAdminRole($user) || $user->can('delete_any_allowance::period');
    }
}
