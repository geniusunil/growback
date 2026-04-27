<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'reminder_sound',
        'reminder_vibration',
        'show_in_drawer',
        'notification_sound',
        'notification_vibration',
        'show_full_screen',
        'custom_sound_path',
      
    ];

    protected $casts = [
        'reminder_times' => 'array',
        'reminder_vibration' => 'boolean',
        'show_in_drawer' => 'boolean',
        'notification_sound' => 'boolean',
        'notification_vibration' => 'boolean',
        'show_full_screen' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
