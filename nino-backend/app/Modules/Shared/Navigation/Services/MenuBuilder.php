<?php

namespace App\Modules\Shared\Navigation\Services;

use App\Modules\Shared\Navigation\DTOs\MenuItem;

class MenuBuilder
{
    public function __construct(
        protected MenuRegistry $registry,
        protected MenuVisibilityResolver $visibilityResolver,
        protected ActiveMenuResolver $activeResolver
    ) {}

    /**
     * Builds the final nested, sorted, active-resolved, visibility-filtered menu tree.
     *
     * @return MenuItem[]
     */
    public function build(): array
    {
        $rawItems = $this->registry->all();
        $filtered = [];

        // 1. Filter Visibility & Clone
        foreach ($rawItems as $item) {
            if ($this->visibilityResolver->isVisible($item)) {
                $cloned = clone $item;
                $cloned->children = []; // Ensure children array is cleanly reset on clone
                $filtered[$item->key] = $cloned;
            }
        }

        // 2. Build Tree Structure
        $tree = [];
        foreach ($filtered as $key => $item) {
            if ($item->parentKey && isset($filtered[$item->parentKey])) {
                // Link to parent
                $filtered[$item->parentKey]->children[] = $item;
            } else if (!$item->parentKey) {
                // Root node
                $tree[] = $item;
            }
            // If parentKey is set but parent is not in $filtered, child is omitted.
        }

        // 3. Resolve active states & sort recursively
        $tree = $this->finalizeTree($tree);

        return $tree;
    }

    /**
     * @param MenuItem[] $nodes
     * @return MenuItem[]
     */
    protected function finalizeTree(array $nodes): array
    {
        $finalized = [];

        // Sort by order ASC
        usort($nodes, function(MenuItem $a, MenuItem $b) {
            return $this->effectiveOrder($a) <=> $this->effectiveOrder($b);
        });

        foreach ($nodes as $node) {
            if (!empty($node->children)) {
                $node->children = $this->finalizeTree($node->children);
            }

            if ($node->isHeader && empty($node->children)) {
                continue;
            }

            // Determine active state out of the current routing context
            $node->_isActive = $this->activeResolver->isActive($node);
            $finalized[] = $node;
        }

        return $finalized;
    }

    protected function effectiveOrder(MenuItem $item): int
    {
        $user = auth()->user();

        if (
            $user
            && ! empty($item->priorityRoles)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole($item->priorityRoles)
        ) {
            return $item->order - $item->priorityBoost;
        }

        return $item->order;
    }
}
