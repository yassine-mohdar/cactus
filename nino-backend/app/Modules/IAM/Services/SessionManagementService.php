<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SessionManagementService
{
    public function invalidateUserSessions(User $user, ?string $exceptSessionId = null): int
    {
        $table = (string) config('session.table', 'sessions');

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table)
            ->where('user_id', $user->getKey());

        if ($exceptSessionId !== null && $exceptSessionId !== '') {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }

    public function revokeUserAccess(User $user, ?string $exceptSessionId = null): int
    {
        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->saveQuietly();

        return $this->invalidateUserSessions($user, $exceptSessionId);
    }
}
