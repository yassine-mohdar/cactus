<?php

namespace App\Modules\Shared\Navigation\Services;

use App\Modules\IAM\Services\ScopeAuthorizationService;
use App\Modules\Shared\Navigation\DTOs\MenuItem;

class MenuVisibilityResolver
{
    public function __construct(
        private readonly ScopeAuthorizationService $scopes = new ScopeAuthorizationService(),
    ) {}

    public function isVisible(MenuItem $item): bool
    {
        $user = auth()->user();

        // If no user, generally we don't show admin menus
        if (!$user) {
            return false;
        }

        // If the item demands specific permissions, check if user has at least one of them
        // Or if you prefer "has all", adjust logic. Usually for menus, "any" permission implies access to the root node.
        if (!empty($item->permissions)) {
            $hasPermission = false;
            foreach ($item->permissions as $permission) {
                if ($user->can($permission)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                return false;
            }
        }

        if (!empty($item->scopes) && !$this->scopes->allowsRequiredScopes($user, $item->scopes)) {
            return false;
        }

        return true;
    }
}
