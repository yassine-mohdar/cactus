<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserAvatarService
{
    public function replace(User $user, UploadedFile $avatar, string $disk = 'public'): string
    {
        $oldAvatar = $user->avatar;
        $path = $avatar->store('avatars', $disk);

        $user->forceFill([
            'avatar' => $path,
        ])->save();

        if (is_string($oldAvatar) && $oldAvatar !== '' && $oldAvatar !== $path && Storage::disk($disk)->exists($oldAvatar)) {
            Storage::disk($disk)->delete($oldAvatar);
        }

        return $path;
    }
}
