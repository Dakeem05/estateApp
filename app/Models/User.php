<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\Api\V1\ApiResetPasswordEmail;
use App\Notifications\Api\V1\ApiVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'state',
        'town',
        'lga',
        'bvn',
        'document_type',
        'offers_declined',
        'document',
        'role',
        'isVerified',
        'id_number',
        'user_verified_at'
    ];

    public function sendApiEmailForgotPasswordNotification()
    {
       $this->notify(new ApiResetPasswordEmail);
    }
    
    public function sendApiVerifyEmailNotification()
    {
       $this->notify(new ApiVerifyEmail);
    }

    
    public function notifications (): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    public function transactions (): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }

    public function wallet (): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id');
    }
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_verified_at' => 'datetime',
        'password' => 'hashed',
        'isVerified' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
