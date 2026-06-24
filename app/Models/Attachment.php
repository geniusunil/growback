<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'user_id',
        'guest_id',
        'activity_id',
        'file_name',
        'file_size'
    ];

public function activity()
{
    return $this->belongsTo(Activity::class);
}

protected $appends = ['file_url'];
public function getFileUrlAttribute()
{
    return asset('storage/attachments/' . $this->file_name);
}
}