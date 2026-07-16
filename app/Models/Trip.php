<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Trip extends Model
{
    protected $fillable = [
        'passenger_id',
        'driver_id',
        'pricing_type',
        'estimated_price',
        'final_price',
    ];

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function driver(): BelongsTo 
    {
       return $this->belongsTo(User::class, 'driver_id');
    }
}
