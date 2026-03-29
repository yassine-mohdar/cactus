<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use App\Modules\Settings\Services\MediaStorageSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserAvatarService
{
    public function __construct(
        private readonly MediaStorageSettingsService $mediaSettings,
    ) {}

    public function replace(User $user, UploadedFile $avatar, ?string $disk = null): string
    {
        $disk ??= $this->mediaSettings->defaultDisk();
        $oldAvatar = $user->avatar;
        $path = $avatar->store($this->mediaSettings->avatarDirectory(), $disk);

        $user->forceFill([
            'avatar' => $path,
        ])->save();

        if (is_string($oldAvatar) && $oldAvatar !== '' && $oldAvatar !== $path && Storage::disk($disk)->exists($oldAvatar)) {
            Storage::disk($disk)->delete($oldAvatar);
        }

        return $path;
    }
}
