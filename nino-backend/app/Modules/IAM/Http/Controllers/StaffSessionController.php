<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\IAM\Services\SessionManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffSessionController extends Controller
{
    public function __construct(
        private readonly SessionManagementService $sessions,
        private readonly AuditLogger $audit,
    ) {}

    public function store(Request $request, User $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        abort_unless($staff->isStaff(), 404);

        $keepCurrentSession = auth()->id() === $staff->id;
        $revokedSessions = $this->sessions->revokeUserAccess(
            $staff,
            $keepCurrentSession ? $request->session()->getId() : null,
        );

        $this->audit->log(
            action: 'staff.sessions.revoked',
            target: $staff,
            newValues: [
                'revoked_sessions' => $revokedSessions,
                'reason' => 'manual',
                'kept_current_session' => $keepCurrentSession,
            ],
            notes: 'Staff sessions revoked manually',
            context: [
                'module' => 'iam',
                'source' => 'staff_session_controller',
            ],
        );

        return back()->with(
            'success',
            $keepCurrentSession
                ? 'Other active sessions were revoked successfully.'
                : 'All active sessions were revoked successfully.'
        );
    }
}
