<?php

namespace App\Http\Controllers;

use App\Models\PlanOfAction;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PrintPlanOfActionController extends Controller
{
    public function recap(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Only admin and super_admin can view recap
        if (!$user || !($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            abort(403, 'Unauthorized');
        }

        // Get all POAs grouped by team
        $poas = PlanOfAction::with(['user', 'module', 'subModule'])
            ->whereNotNull('user_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($poa) {
                // Group by team - if you add company relationship later, use: $poa->user?->company?->name
                return 'MEDIKCARE';
            });
        
        // Get all team members (users who are not admin/super_admin)
        $allTeamMembers = User::role(['team_member', 'lead'])
            ->orderBy('name')
            ->get();
        
        // Get submitted user IDs
        $submittedUserIds = PlanOfAction::whereNotNull('user_id')
            ->pluck('user_id')
            ->unique()
            ->toArray();
        
        // Get members who haven't submitted
        $notSubmittedMembers = $allTeamMembers->filter(function ($member) use ($submittedUserIds) {
            return !in_array($member->id, $submittedUserIds);
        });
        
        return view('poa.recap', [
            'poas' => $poas,
            'allTeamMembers' => $allTeamMembers,
            'submittedCount' => count($submittedUserIds),
            'notSubmittedMembers' => $notSubmittedMembers,
        ]);
    }
}
