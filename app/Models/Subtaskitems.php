<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subtaskitems extends Model
{
    protected $table = 'subtask_items';

    protected $fillable = [
        'subtask_id',
        'title',
        'is_done',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // Parent activity subtask
    public function subtask(): BelongsTo
    {
        return $this->belongsTo(
            ActivitySubtask::class,
            'subtask_id'
        );
    }
}
