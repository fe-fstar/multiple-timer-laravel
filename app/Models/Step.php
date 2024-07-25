<?php

namespace App\Models;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Step extends Model
{
    protected $table = 'steps';

    public function plan(): BelongsTo {
        return $this->belongsTo(Plan::class);
    }

    protected $fillable = [
        'id',
        'title',
        'description',
        'seconds',
        'minutes',
        'hours'
    ];
}
