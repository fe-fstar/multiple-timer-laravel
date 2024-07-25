<?php

namespace App\Models;

use App\Models\Step;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    protected $table = 'plans';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany {
        return $this->hasMany(Step::class);
    }

    protected $fillable = [
        'title',
        'description',
        'seconds',
        'minutes',
        'hours'
    ];
}
