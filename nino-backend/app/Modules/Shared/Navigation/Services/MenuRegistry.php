<?php

namespace App\Modules\Shared\Navigation\Services;

use App\Modules\Shared\Navigation\DTOs\MenuItem;

class MenuRegistry
{
    /** @var MenuItem[] */
    protected array $items = [];

    public function add(MenuItem $item): self
    {
        $this->items[$item->key] = $item;
        return $this;
    }

    /**
     * @return MenuItem[]
     */
    public function all(): array
    {
        return $this->items;
    }
}
