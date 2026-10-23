<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivitySubtask extends Model
{
    protected $table = 'activity_subtasks';

    protected $fillable = [
        'parent_activity_id',
        'title',
        'description',
        'priority',
        'due_date',
        'reminder_times',
        'repeat_enabled',
        'frequency_unit',
        'frequency_value',
        'weekly_days',
        'is_mandatory_gap',
        'mandatory_gap_value',
        'mandatory_gap_unit',
        'duration_unit',
        'duration_value',
        'time_window_enabled',
        'time_window_mode',
        'time_window_ranges',
        'days_of_week_enabled',
        'days_of_week_mode',
        'days_of_week',
        'public_holidays_enabled',
        'public_holidays_mode',
        'repeat_window',
        'show_full_screen',
        'reminder_sound',
        'reminder_vibration',
        'show_in_drawer',
        'notification_sound',
        'notification_vibration',
        'custom_sound_path',
        'urls',
        'is_completed',
        'completed_at',
        'snoozed_until',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'reminder_times' => 'array',
            'repeat_enabled' => 'boolean',
            'frequency_value' => 'integer',
            'weekly_days' => 'array',
            'is_mandatory_gap' => 'boolean',
            'mandatory_gap_value' => 'integer',
            'duration_value' => 'decimal:2',
            'time_window_enabled' => 'boolean',
            'time_window_ranges' => 'array',
            'days_of_week_enabled' => 'boolean',
            'days_of_week' => 'array',
            'public_holidays_enabled' => 'boolean',
            'repeat_window' => 'array',
            'show_full_screen' => 'boolean',
            'reminder_vibration' => 'boolean',
            'show_in_drawer' => 'boolean',
            'notification_sound' => 'boolean',
            'notification_vibration' => 'boolean',
            'urls' => 'array',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    // Parent Activity
    // public function parentActivity(): BelongsTo
    // {
    //     return $this->belongsTo(
    //         Activity::class,
    //         'parent_activity_id'
    //     );
    // }

    // Child Subtask Items
    public function items(): HasMany
    {
        return $this->hasMany(
            SubtaskItem::class,
            'subtask_id'
        )->orderBy('sort_order');
    }
}
