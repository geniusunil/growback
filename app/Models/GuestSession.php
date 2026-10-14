<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
    protected $fillable = [
        'guest_id',
        'token_hash',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}