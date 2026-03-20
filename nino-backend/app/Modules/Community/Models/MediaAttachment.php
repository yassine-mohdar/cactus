<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Modules\Community\Enums\MediaAttachmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaAttachment extends Model
{
    protected $table = 'community_media_attachments';

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'user_id',
        'type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'alt_text',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'type' => MediaAttachmentType::class,
        'size_bytes' => 'integer',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(CommunityReport::class, 'reportable');
    }
}
