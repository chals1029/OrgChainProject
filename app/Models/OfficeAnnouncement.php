<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OfficeAnnouncement extends Model
{
    protected $connection = 'mysql';

    public const PUBLICATION_TYPES = ['General Announcement', 'Deadline', 'Guideline', 'Reminder'];

    protected $fillable = [
        'title',
        'type',
        'body',
        'author',
        'priority',
        'attachment_path',
        'attachment_name',
        'expires_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function scopePublished(Builder $query): Builder
    {
        $now = now();

        return $query->where(fn (Builder $expiry) => $expiry->whereNull('expires_at')->orWhere('expires_at', '>', $now));
    }

    /**
     * Announcements are campus-wide publications; no office draft rows are stored here.
     * Keep every student surface on the same persisted publication metadata.
     *
     * @return array<string, mixed>
     */
    public function studentPublication(): array
    {
        $category = match ($this->type) {
            'General', 'General Announcement' => 'general',
            'TOSA', 'Call for TOSA' => 'tosa',
            'Deadline', 'Deadline Notice', 'Reminder', 'Compliance Reminder' => 'deadlines',
            default => 'oso',
        };
        [$tag, $pill] = match ($category) {
            'general' => ['General Announcements', 'sp-pill-general'],
            'deadlines' => ['Deadline Reminders', 'sp-pill-deadlines'],
            'tosa' => ['Call for TOSA', 'sp-pill-tosa'],
            default => ['OSO Notices', 'sp-pill-oso'],
        };
        $date = $this->created_at;

        return [
            'id' => 'a'.$this->id,
            'announcement_id' => (int) $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'body' => $this->body,
            'author' => $this->author ?: 'OSO Admin',
            'priority' => $this->priority ?: 'normal',
            'category' => $category,
            'tag' => $tag,
            'pill_class' => $pill,
            'time' => $date?->diffForHumans() ?? '',
            'published_at' => $date?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'date' => $date?->format('M j, Y') ?? '',
            'day' => $date?->format('d M') ?? '',
            'year' => $date?->format('Y') ?? '',
            'sort' => $date?->timestamp ?? 0,
            'url' => route('portal.home', ['announcement' => $this->id]),
        ];
    }
}
