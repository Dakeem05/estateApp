<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'parking_space' => 'boolean',
        'is_available' => 'boolean',
        'amenities' => 'object',
        'images' => 'object',
    ];

    public function appointments (): HasMany
    {
        return $this->hasMany(Appointment::class, 'appartment_id', 'id');
    }

    public function orders (): HasMany
    {
        return $this->hasMany(Order::class, 'appartment_id', 'id');
    }
}
