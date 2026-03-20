<?php

namespace App\Modules\Shared\Navigation\DTOs;

class MenuItem
{
    public string $key;
    public ?string $module = null;
    public ?string $label = null;
    public ?string $icon = null;
    public ?string $routeName = null;
    public array $routeParams = [];
    public ?string $parentKey = null;
    public int $order = 0;
    public array $permissions = [];
    public array $scopes = [];
    public array $priorityRoles = [];
    public int $priorityBoost = 0;
    public ?string $featureFlag = null;
    public array $activePatterns = [];
    public $badgeResolver = null;
    public bool $isHeader = false;
    public array $children = [];
    
    // Runtime property not strictly DTO
    public bool $_isActive = false;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function asHeader(bool $val = true): self
    {
        $this->isHeader = $val;
        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setRoute(string $routeName, array $params = []): self
    {
        $this->routeName = $routeName;
        $this->routeParams = $params;
        return $this;
    }

    public function setParent(string $parentKey): self
    {
        $this->parentKey = $parentKey;
        return $this;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function requirePermission(string|array $permissions): self
    {
        $this->permissions = (array) $permissions;
        return $this;
    }

    public function requireScope(string|array $scopes): self
    {
        $this->scopes = (array) $scopes;
        return $this;
    }

    public function prioritizeForRoles(string|array $roles, int $boost = 100): self
    {
        $this->priorityRoles = array_values(array_unique(array_merge($this->priorityRoles, (array) $roles)));
        $this->priorityBoost = max($this->priorityBoost, $boost);

        return $this;
    }

    public function activeWhen(string|array $patterns): self
    {
        $this->activePatterns = (array) $patterns;
        return $this;
    }
}
