<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use App\Modules\IAM\Notifications\StaffAccessSetupNotification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

class StaffAccessSetupService
{
    public function createSetupUrl(User $staff, string $source = 'staff_access_setup'): string
    {
        $this->ensureStaffUser($staff);

        return URL::temporarySignedRoute(
            'staff.access.setup.show',
            now()->addMinutes($this->expiresInMinutes()),
            [
                'token' => Password::broker($this->broker())->createToken($staff),
                'email' => $staff->email,
                'source' => $source,
            ],
        );
    }

    public function dispatchSetupLink(User $staff, string $source = 'staff_access_setup'): string
    {
        $setupUrl = $this->createSetupUrl($staff, $source);

        $staff->notify(new StaffAccessSetupNotification(
            setupUrl: $setupUrl,
            expiryMinutes: $this->expiresInMinutes(),
            source: $source,
        ));

        return $setupUrl;
    }

    public function expiresInMinutes(): int
    {
        return max(1, (int) config('auth.passwords.'.$this->broker().'.expire', 60));
    }

    private function broker(): string
    {
        return (string) config('fortify.passwords', config('auth.defaults.passwords', 'users'));
    }

    private function ensureStaffUser(User $staff): void
    {
        if (! $staff->isStaff()) {
            throw new InvalidArgumentException('Staff access setup links can only be issued for staff accounts.');
        }
    }
}
