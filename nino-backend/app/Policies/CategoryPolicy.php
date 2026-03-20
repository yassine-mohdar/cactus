<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Catalog\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('categories.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->can('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.delete');
    }
}
