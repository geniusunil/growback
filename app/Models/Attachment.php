<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'user_id',
        'activity_id',
        'file_name',
    ];

public function activity()
{
    return $this->belongsTo(Activity::class);
}
}