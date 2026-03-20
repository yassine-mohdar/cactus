<?php

namespace App\Modules\Community\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case HIDDEN = 'hidden';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::HIDDEN => 'Hidden',
            self::ARCHIVED => 'Archived',
        };
    }
}
