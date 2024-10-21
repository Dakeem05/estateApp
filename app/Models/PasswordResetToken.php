<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =[
        // 'created_at' => 'datetime',
        'otp_verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function GenerateOtp($email){
        $otp = random_int(100000, 999999);
        $time = Carbon::now();


        $instance = PasswordResetToken::where('email', $email)->first();
        if($instance !== null){
            $instance->delete();
            PasswordResetToken::create([
                'email' => $email,
                'token' => $otp,
                'expires_at' => $time->addMinutes(30),
            ]);
        } else {
            PasswordResetToken::create([
                'email' => $email,
                'token' => $otp, 
                'expires_at' => $time->addMinutes(30),
            ]);
        }
    }
}
