<?php

namespace App\Modules\Community\Services;

use App\Models\User;
use App\Modules\Community\Enums\GroupMembershipRole;
use App\Modules\Community\Enums\GroupMembershipStatus;
use App\Modules\Community\Models\GroupMembership;

class CommunityOnboardingService
{
    public function __construct(
        protected DefaultCommunityGroupService $defaultCommunityGroupService,
    ) {}

    public function inviteNewCustomerToDefaultGroup(User $customer, string $source = 'customer_onboarding'): ?GroupMembership
    {
        if (! $this->shouldInvite($customer)) {
            return null;
        }

        $group = $this->defaultCommunityGroupService->getOrCreate();

        $existingMembership = GroupMembership::query()
            ->where('group_id', $group->id)
            ->where('user_id', $customer->id)
            ->first();

        if ($existingMembership) {
            $this->markCustomerInvitationTimestamp($customer, $existingMembership->created_at);

            return $existingMembership;
        }

        $membership = GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $customer->id,
            'role' => GroupMembershipRole::MEMBER,
            'status' => GroupMembershipStatus::INVITED,
            'metadata' => [
                'source' => $source,
                'default_group' => true,
                'invited_via' => 'customer_onboarding',
            ],
        ]);

        $this->markCustomerInvitationTimestamp($customer, $membership->created_at);

        return $membership;
    }

    public function shouldInvite(User $customer): bool
    {
        return $customer->isCustomer()
            && $customer->isActive()
            && (bool) $customer->community_auto_invite_to_default_group;
    }

    protected function markCustomerInvitationTimestamp(User $customer, $timestamp): void
    {
        if ($customer->community_default_group_invited_at !== null) {
            return;
        }

        $customer->forceFill([
            'community_default_group_invited_at' => $timestamp ?? now(),
        ])->saveQuietly();
    }
}
