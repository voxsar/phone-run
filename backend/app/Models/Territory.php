<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Territory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'polygon',
        'area',
        'color',
        'is_active',
    ];

    protected $casts = [
        'polygon' => 'array',
        'area' => 'float',
        'is_active' => 'boolean',
    ];

    protected $appends = ['user_name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUserNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }
}
