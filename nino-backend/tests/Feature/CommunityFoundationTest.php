<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Community\Enums\CommunityReportReason;
use App\Modules\Community\Enums\CommunityReportState;
use App\Modules\Community\Enums\GroupJoinRequestStatus;
use App\Modules\Community\Enums\GroupMembershipPolicy;
use App\Modules\Community\Enums\GroupMembershipRole;
use App\Modules\Community\Enums\GroupMembershipStatus;
use App\Modules\Community\Enums\MediaAttachmentType;
use App\Modules\Community\Enums\ModerationQueueStatus;
use App\Modules\Community\Enums\PostStatus;
use App\Modules\Community\Enums\ReactionType;
use App\Modules\Community\Models\CommunityReport;
use App\Modules\Community\Models\Comment;
use App\Modules\Community\Models\Group;
use App\Modules\Community\Models\GroupJoinRequest;
use App\Modules\Community\Models\GroupMembership;
use App\Modules\Community\Models\MediaAttachment;
use App\Modules\Community\Models\ModerationQueueItem;
use App\Modules\Community\Models\Post;
use App\Modules\Community\Models\Reaction;
use App\Modules\Community\Services\CommunityOnboardingService;
use App\Modules\Community\Services\DefaultCommunityGroupService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommunityFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_community_group_service_creates_single_system_group(): void
    {
        $service = app(DefaultCommunityGroupService::class);

        $group = $service->getOrCreate();
        $sameGroup = $service->getOrCreate();

        $this->assertSame($group->id, $sameGroup->id);
        $this->assertTrue($group->isDefaultGroup());
        $this->assertSame(GroupMembershipPolicy::OPEN, $group->membership_policy);
    }

    public function test_group_membership_and_join_request_models_cast_domain_enums(): void
    {
        $user = User::factory()->create();

        $group = Group::create([
            'name' => 'Collectors Club',
            'slug' => 'collectors-club',
            'membership_policy' => GroupMembershipPolicy::APPROVAL,
        ]);

        $membership = GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMembershipRole::MEMBER,
            'status' => GroupMembershipStatus::ACTIVE,
            'joined_at' => now(),
        ]);

        $joinRequest = GroupJoinRequest::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => GroupJoinRequestStatus::PENDING,
            'message' => 'I would like to join.',
        ]);

        $joinRequest->approve($user, 'Approved for early access.');

        $this->assertSame(GroupMembershipRole::MEMBER, $membership->role);
        $this->assertSame(GroupMembershipStatus::ACTIVE, $membership->status);
        $this->assertSame(GroupJoinRequestStatus::APPROVED, $joinRequest->fresh()->status);
        $this->assertTrue($group->allowsJoinRequests());
    }

    public function test_community_post_media_comment_and_reaction_models_support_core_relationships(): void
    {
        $user = User::factory()->create();

        $group = Group::create([
            'name' => 'Collectors Club',
            'slug' => 'collectors-club-content',
            'membership_policy' => GroupMembershipPolicy::OPEN,
        ]);

        $post = Post::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'title' => 'Rare drop',
            'body' => 'Sharing a first look at the latest collectible drop.',
            'status' => PostStatus::DRAFT,
        ]);

        $post->publish();

        $postMedia = MediaAttachment::create([
            'attachable_type' => Post::class,
            'attachable_id' => $post->id,
            'user_id' => $user->id,
            'type' => MediaAttachmentType::IMAGE,
            'path' => 'community/posts/rare-drop.jpg',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'This launch looks strong.',
        ]);

        $reply = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $comment->id,
            'body' => 'Agreed, the artwork is much better than the last release.',
        ]);

        $commentMedia = MediaAttachment::create([
            'attachable_type' => Comment::class,
            'attachable_id' => $comment->id,
            'user_id' => $user->id,
            'type' => MediaAttachmentType::FILE,
            'path' => 'community/comments/spec-sheet.pdf',
        ]);

        $postReaction = Reaction::create([
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
            'user_id' => $user->id,
            'type' => ReactionType::LIKE,
        ]);

        $commentReaction = Reaction::create([
            'reactable_type' => Comment::class,
            'reactable_id' => $comment->id,
            'user_id' => $user->id,
            'type' => ReactionType::SUPPORT,
        ]);

        $comment->markEdited();

        $this->assertSame(PostStatus::PUBLISHED, $post->fresh()->status);
        $this->assertSame(MediaAttachmentType::IMAGE, $postMedia->type);
        $this->assertSame(MediaAttachmentType::FILE, $commentMedia->type);
        $this->assertSame(ReactionType::LIKE, $postReaction->type);
        $this->assertSame(ReactionType::SUPPORT, $commentReaction->type);
        $this->assertCount(1, $group->posts);
        $this->assertCount(1, $post->mediaAttachments);
        $this->assertCount(2, $post->comments);
        $this->assertSame($comment->id, $reply->parent?->id);
        $this->assertCount(1, $comment->replies);
        $this->assertTrue($comment->fresh()->is_edited);
    }

    public function test_community_reports_and_moderation_queue_support_review_lifecycle(): void
    {
        $reporter = User::factory()->create();
        $moderator = User::factory()->create();

        $group = Group::create([
            'name' => 'Moderated Collectors',
            'slug' => 'moderated-collectors',
        ]);

        $post = Post::create([
            'group_id' => $group->id,
            'user_id' => $reporter->id,
            'body' => 'Questionable community post for moderation flow.',
            'status' => PostStatus::PUBLISHED,
        ]);

        $report = CommunityReport::create([
            'group_id' => $group->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reporter_id' => $reporter->id,
            'reason' => CommunityReportReason::SPAM,
            'description' => 'Looks like promotional spam.',
        ]);

        $queueItem = $report->queueForModeration(10);
        $queueItem->assignTo($moderator);
        $queueItem->markInReview($moderator);
        $queueItem->resolve($moderator, 'Post hidden and warning issued.');

        $this->assertSame(CommunityReportReason::SPAM, $report->reason);
        $this->assertSame(CommunityReportState::ACTION_TAKEN, $report->fresh()->state);
        $this->assertSame(ModerationQueueStatus::RESOLVED, $queueItem->fresh()->status);
        $this->assertSame($moderator->id, $queueItem->assigned_to);
        $this->assertSame($queueItem->id, $report->fresh()->queueItem?->id);
        $this->assertSame($post->id, $queueItem->moderatable?->id);
    }

    public function test_permissions_seeder_assigns_community_moderation_hooks(): void
    {
        $this->seed(PermissionsSeeder::class);

        $communityModerator = Role::findByName('Community Moderator', 'web');

        $this->assertTrue($communityModerator->hasPermissionTo('community.viewAny'));
        $this->assertTrue($communityModerator->hasPermissionTo('community.reports.viewAny'));
        $this->assertTrue($communityModerator->hasPermissionTo('community.reports.update'));
        $this->assertTrue($communityModerator->hasPermissionTo('community.moderation.viewAny'));
        $this->assertTrue($communityModerator->hasPermissionTo('community.moderation.update'));
    }

    public function test_customer_onboarding_service_creates_default_group_invitation_for_active_customers(): void
    {
        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
            'community_auto_invite_to_default_group' => true,
        ]);

        $membership = app(CommunityOnboardingService::class)
            ->inviteNewCustomerToDefaultGroup($customer, 'feature_test');

        $this->assertNotNull($membership);
        $this->assertSame(GroupMembershipStatus::INVITED, $membership->status);
        $this->assertTrue((bool) $membership->metadata['default_group']);
        $this->assertSame('feature_test', $membership->metadata['source']);
        $this->assertNotNull($customer->fresh()->community_default_group_invited_at);
    }

    public function test_customer_onboarding_service_respects_auto_invite_opt_out(): void
    {
        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
            'community_auto_invite_to_default_group' => false,
        ]);

        $membership = app(CommunityOnboardingService::class)
            ->inviteNewCustomerToDefaultGroup($customer, 'feature_test');

        $this->assertNull($membership);
        $this->assertNull($customer->fresh()->community_default_group_invited_at);
        $this->assertSame(0, GroupMembership::query()->count());
    }
}
