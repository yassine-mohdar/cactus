<?php

namespace App\Modules\Shared\Navigation\Services;

use App\Modules\Shared\Navigation\DTOs\MenuItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ActiveMenuResolver
{
    /**
     * Determines if a given MenuItem should be marked as active.
     */
    public function isActive(MenuItem $item): bool
    {
        $currentRoute = Route::currentRouteName();

        if (!$currentRoute) {
            return false;
        }

        // Direct route match
        if ($item->routeName === $currentRoute) {
            return true;
        }

        // Pattern match
        foreach ($item->activePatterns as $pattern) {
            if (Str::is($pattern, $currentRoute)) {
                return true;
            }
        }

        // Parent is active if any of its children are active
        if (!empty($item->children)) {
            foreach ($item->children as $child) {
                if ($this->isActive($child)) {
                    return true;
                }
            }
        }

        return false;
    }
}
