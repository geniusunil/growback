<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'email',
        'title',
        'description',
        'category',
       'start_time',
    'end_time'
    ];
}