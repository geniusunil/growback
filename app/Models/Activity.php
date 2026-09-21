<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Attachment;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'guest_id',
        'title',
        'description',
        'category',
        'reminder_times',
        'frequency_unit',
        'frequency_value',

        // Repeat
        'repeat_enabled',

        'reminder_sound',
        'reminder_vibration',
        'priority',
        'thumbnail',
        'show_in_drawer',
        'notification_sound',
        'notification_vibration',
        'show_full_screen',
        'custom_sound_path',
        'due_date',
        'is_completed',
        'completed_at',
        'duration_value',
        'duration_unit',

        // Empty stomach condition
        'empty_stomach_value',
        'empty_stomach_unit',
        'empty_stomach_after',

        // URLs
        'urls',

        // Mandatory gap
        'is_mandatory_gap',
        'mandatory_gap_value',
        'mandatory_gap_unit',

        'time_range_enabled',
        'time_range_mode',
        'time_ranges',

        'weekday_enabled',
        'weekday_mode',
        'weekdays',

        'holiday_enabled',
        'holiday_mode',
    ];

    protected $casts = [
        'reminder_times' => 'array',

        'reminder_vibration' => 'boolean',
        'show_in_drawer' => 'boolean',
        'notification_sound' => 'boolean',
        'notification_vibration' => 'boolean',
        'show_full_screen' => 'boolean',

        'is_completed' => 'boolean',
        'repeat_enabled' => 'boolean',

        'due_date' => 'datetime',
        'completed_at' => 'datetime',

        'duration_value' => 'float',

        // Empty stomach condition
        'empty_stomach_value' => 'integer',
        'empty_stomach_unit' => 'string',
        'empty_stomach_after' => 'string',

        // URLs
        'urls' => 'array',



        // Restriction fields
        'time_range_enabled' => 'boolean',
        'time_ranges' => 'array',

        'weekday_enabled' => 'boolean',
        'weekdays' => 'array',

        'holiday_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    protected $appends = [
        'thumbnail_url'
    ];

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail
            ? asset('storage/thumbnails/' . $this->thumbnail)
            : null;
    }
}
