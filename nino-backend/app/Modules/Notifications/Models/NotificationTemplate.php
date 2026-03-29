<?php

namespace App\Modules\Notifications\Models;

use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'event', 'channel', 'name', 'subject', 'body', 'is_enabled', 'metadata',
    ];

    protected $casts = [
        'event' => NotificationEvent::class,
        'channel' => NotificationChannel::class,
        'is_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────
    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'template_id');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForEvent($query, NotificationEvent $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForChannel($query, NotificationChannel $channel)
    {
        return $query->where('channel', $channel);
    }

    // ── Helpers ─────────────────────────────────────────────
    /**
     * Render the template body by substituting {{placeholder}} variables.
     */
    public function render(array $variables): string
    {
        $body = $this->body;
        foreach ($variables as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
        }
        return $body;
    }

    /**
     * Render the subject line with variable substitution.
     */
    public function renderSubject(array $variables): ?string
    {
        if (!$this->subject) {
            return null;
        }

        $subject = $this->subject;
        foreach ($variables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', (string) $value, $subject);
        }
        return $subject;
    }

    /**
     * Get a preview of the template with sample data.
     */
    public function preview(): string
    {
        $sampleVars = [];
        foreach ($this->event->availableVariables() as $var) {
            $sampleVars[$var] = '[[' . strtoupper($var) . ']]';
        }
        return $this->render($sampleVars);
    }
}
