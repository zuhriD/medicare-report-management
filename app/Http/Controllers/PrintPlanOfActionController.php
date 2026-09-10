<?php

namespace App\Http\Controllers;

use App\Models\PlanOfAction;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PrintPlanOfActionController extends Controller
{
    public function recap(\Illuminate\Http\Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Only admin and super_admin can view recap
        if (!$user || !($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            abort(403, 'Unauthorized');
        }

        $rawDate = $request->input('date', now()->toDateString());

        try {
            if (is_string($rawDate) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
                $dateObj = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $rawDate);
            } else {
                $dateObj = \Illuminate\Support\Carbon::parse($rawDate);
            }
        } catch (\Throwable $e) {
            $dateObj = now();
        }

        $selectedDate = $dateObj->format('Y-m-d');

        // Get all POAs for the selected date grouped by team
        $poas = PlanOfAction::with(['user', 'module', 'subModule'])
            ->whereNotNull('user_id')
            ->whereDate('start_date', $selectedDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($poa) {
                return 'MEDIKCARE';
            });
        
        // Get all team members (users who are not admin/super_admin)
        $allTeamMembers = User::role(['team_member', 'lead'])
            ->orderBy('name')
            ->get();
        
        // Get submitted user IDs for the selected date
        $submittedUserIds = PlanOfAction::whereNotNull('user_id')
            ->whereDate('start_date', $selectedDate)
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
            'selectedDate' => $selectedDate,
        ]);
    }
}
