<?php

namespace App\Modules\Organizations\Services;

use App\Modules\Organizations\Models\Organization;
use InvalidArgumentException;

class OrganizationHierarchyService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createPlatform(array $attributes): Organization
    {
        return Organization::create(array_merge([
            'type' => Organization::TYPE_PLATFORM,
            'status' => Organization::STATUS_ACTIVE,
            'code' => $attributes['code'] ?? 'PLT-001',
        ], $attributes, [
            'type' => Organization::TYPE_PLATFORM,
            'parent_id' => null,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createFranchise(Organization $platform, array $attributes): Organization
    {
        if (! $platform->isPlatform()) {
            throw new InvalidArgumentException('Franchise organizations must belong to a platform.');
        }

        return Organization::create(array_merge([
            'type' => Organization::TYPE_FRANCHISE,
            'status' => Organization::STATUS_ACTIVE,
            'code' => $attributes['code'] ?? $this->nextCode(Organization::TYPE_FRANCHISE),
        ], $attributes, [
            'type' => Organization::TYPE_FRANCHISE,
            'parent_id' => $platform->id,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createBranch(Organization $franchise, array $attributes): Organization
    {
        if (! $franchise->isFranchise()) {
            throw new InvalidArgumentException('Branch organizations must belong to a franchise.');
        }

        return Organization::create(array_merge([
            'type' => Organization::TYPE_BRANCH,
            'status' => Organization::STATUS_ACTIVE,
            'code' => $attributes['code'] ?? $this->nextCode(Organization::TYPE_BRANCH),
        ], $attributes, [
            'type' => Organization::TYPE_BRANCH,
            'parent_id' => $franchise->id,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function defaultPlatform(array $attributes = []): Organization
    {
        return Organization::platform()->first()
            ?? $this->createPlatform(array_merge([
                'name' => 'NinoWorld Platform',
                'email' => 'platform@ninoworld.com',
                'country' => 'MA',
            ], $attributes));
    }

    private function nextCode(string $type): string
    {
        $prefix = match ($type) {
            Organization::TYPE_PLATFORM => 'PLT',
            Organization::TYPE_FRANCHISE => 'FR',
            Organization::TYPE_BRANCH => 'BR',
            default => 'ORG',
        };

        $count = Organization::query()->where('type', $type)->count() + 1;

        return sprintf('%s-%03d', $prefix, $count);
    }
}
