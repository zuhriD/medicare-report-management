<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LeaveRequestExportController extends Controller
{
    public function exportPdf(Request $request, LeaveRequest $leaveRequest)
    {
        $user = auth()->user();

        // Check permission: user can view own request, or admin/super_admin/lead/manager can view all
        if ($user && method_exists($user, 'hasRole') && !$user->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager'])) {
            if ($leaveRequest->user_id !== $user->id) {
                abort(403, 'Anda tidak memiliki akses untuk mencetak surat izin ini.');
            }
        }

        $leaveRequest->load(['user', 'office', 'approver']);

        $userNameSlug = \Illuminate\Support\Str::slug($leaveRequest->user?->name ?? 'staff');
        $fileName = "surat-izin-{$leaveRequest->id}-{$userNameSlug}.pdf";

        $pdf = Pdf::loadView('leave-requests.pdf', [
            'leave' => $leaveRequest,
        ])->setPaper('a4', 'portrait');

        if ($request->query('view') === '1' || $request->query('preview') === '1') {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }
}
