<?php

namespace App\Models;

use Flutterwave\Entities\Customer;
use Flutterwave\Payload;
use Flutterwave\Service\PayoutSubaccount;
use Flutterwave\Service\VirtualAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

class Wallet extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user () :BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    protected $casts = [
        'account' => 'object',
    ];

    public static function createWallet($id){
        $wallet = Wallet::create([
            'user_id' => $id,
            'amount' => 0.00
        ]);
    }

    public function createAccount($name, $phone, $email){
        $data = array(
            "account_name" => $name,
            "email" => $email,
            "mobilenumber" => $phone,
            "country" => "NG",
            "bank_code" => "035"
        );

        $response = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->post('https://api.flutterwave.com/v3/payout-subaccounts', $data);
        $res = json_decode($response->getBody());
        return $res;
    }

    public function getAccount($account_reference){
        $response_balance = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->get('https://api.flutterwave.com/v3/payout-subaccounts/'.$account_reference.'/balances');
        $res_balance = json_decode($response_balance->getBody());

        $response_account_details = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->get('https://api.flutterwave.com/v3/payout-subaccounts/'.$account_reference);
        $res_account_details = json_decode($response_account_details->getBody());

        return [
            'account' => $res_account_details->data,
            'balance' => $res_balance->data
        ];        
    }
}
