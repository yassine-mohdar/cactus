<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\IAM\Services\StaffAccessSetupService;
use Illuminate\Http\RedirectResponse;

class StaffAccessLinkController extends Controller
{
    public function __construct(
        private readonly StaffAccessSetupService $accessSetup,
        private readonly AuditLogger $audit,
    ) {}

    public function store(User $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        abort_unless($staff->isStaff(), 404);

        if ($staff->status === 'suspended') {
            return back()->withErrors([
                'staff' => 'Suspended staff members cannot receive a setup link until they are reactivated.',
            ]);
        }

        $this->accessSetup->dispatchSetupLink($staff, 'staff_reset_access');

        $this->audit->log(
            action: 'staff.access_setup_link.sent',
            target: $staff,
            newValues: [
                'status' => $staff->status,
                'email' => $staff->email,
            ],
            notes: 'Staff access setup link sent',
            context: [
                'module' => 'iam',
                'source' => 'staff_access_link_controller',
            ],
        );

        return back()->with('success', 'A staff access setup link was sent successfully.');
    }
}
