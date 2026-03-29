<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Enums\GroupMembershipPolicy;
use App\Modules\Community\Enums\GroupVisibility;
use App\Modules\Community\Models\Group;

class DefaultCommunityGroupService
{
    public const DEFAULT_GROUP_SYSTEM_KEY = 'default-community';

    public function getOrCreate(): Group
    {
        return Group::firstOrCreate(
            ['system_key' => self::DEFAULT_GROUP_SYSTEM_KEY],
            $this->defaultAttributes(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultAttributes(): array
    {
        return [
            'name' => 'NinoWorld Community',
            'slug' => 'ninoworld-community',
            'description' => 'Default home group for the future NinoWorld community surface.',
            'visibility' => GroupVisibility::PUBLIC,
            'membership_policy' => GroupMembershipPolicy::OPEN,
            'is_active' => true,
            'metadata' => [
                'is_system_group' => true,
                'foundation' => 'default-community',
            ],
        ];
    }
}
