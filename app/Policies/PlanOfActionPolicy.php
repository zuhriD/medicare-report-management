<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PlanOfAction;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanOfActionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin and super_admin can view all
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Team members can view their own
        if ($user->hasRole('team_member') || $user->hasRole('lead')) {
            return true;
        }
        
        return $user->can('view_any_plan::of::action');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PlanOfAction $planOfAction): bool
    {
        // Admin and super_admin can view all
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Team members can only view their own
        if ($user->hasRole('team_member') || $user->hasRole('lead')) {
            return $planOfAction->user_id === $user->id;
        }
        
        return $user->can('view_plan::of::action');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin and super_admin can create
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Team members can create
        if ($user->hasRole('team_member') || $user->hasRole('lead')) {
            return true;
        }
        
        return $user->can('create_plan::of::action');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PlanOfAction $planOfAction): bool
    {
        // Admin and super_admin can update all
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Team members can only update their own
        if ($user->hasRole('team_member') || $user->hasRole('lead')) {
            return $planOfAction->user_id === $user->id;
        }
        
        return $user->can('update_plan::of::action');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PlanOfAction $planOfAction): bool
    {
        // Admin and super_admin can delete all
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Team members can only delete their own
        if ($user->hasRole('team_member') || $user->hasRole('lead')) {
            return $planOfAction->user_id === $user->id;
        }
        
        return $user->can('delete_plan::of::action');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PlanOfAction $planOfAction): bool
    {
        // Admin and super_admin can restore all
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Team members can only restore their own
        if ($user->hasRole('team_member') || $user->hasRole('lead')) {
            return $planOfAction->user_id === $user->id;
        }
        
        return $user->can('restore_plan::of::action');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PlanOfAction $planOfAction): bool
    {
        // Admin and super_admin can force delete all
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }
        
        // Team members can only force delete their own
        if ($user->hasRole('team_member') || $user->hasRole('lead')) {
            return $planOfAction->user_id === $user->id;
        }
        
        return $user->can('force_delete_plan::of::action');
    }
}
