<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function apartment () :BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'apartment_id', 'id');
    }

    public function setTimeAttribute($value)
    {
        $this->attributes['time'] = Carbon::parse($value);
    }
}
