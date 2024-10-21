<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Wallet;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Http\Resources\Api\V1\WalletCollection;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::where('id', Auth::id())->first();
        $wallet = $user->wallet;
        $account = $wallet->getAccount($wallet->account->account_reference);
        $wallet->update([
            'account' => $account['account'],
            'amount' => $account['balance']->available_balance + $wallet->amount
        ]);
        return ApiResponse::successResponse(['data' => $wallet]);
    }

    public function banks()
    {
        $response = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->get('https://api.flutterwave.com/v3/banks/NG');
        $res = json_decode($response->getBody());

        return ApiResponse::successResponse(['data' => $res->data]);
    }

    public function resolve_account(Request $request)
    {
        $rules = [
            'account_number' => ['required', 'digits:10'],
            'account_bank' => ['required'],
        ];
        $validation = Validator::make($request->all(), $rules);
        if( $validation-> fails()){
            return ApiResponse::validationError([
                "message" => $validation->errors()->first()
            ]);
        }

        $data = array(
            "account_number"=> $request->account_number,
            "account_bank"=> $request->account_bank,
        );
    
        $response_account = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->post('https://api.flutterwave.com/v3/accounts/resolve', $data);
        $res_account = json_decode($response_account->getBody());
        return ApiResponse::successResponse(['data' => $res_account->data]);

    }

    public function transfer(Request $request)
    {
        $rules = [
            'account_bank' => ['required'],
            'account_number' => ['required', 'digits:10'],
            'amount' => ['required', 'numeric', "min:100"],
        ];
        $validation = Validator::make($request->all(), $rules);
        if( $validation-> fails()){
            
            if($validation->errors()->first() == "The amount field must be at least 100."){
                return ApiResponse::validationError([
                    "message" => "Minimum transfer is 100 Naira."
                ]);
            } else {
                return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
            }
        }

        $user = User::where('id', Auth::id())->first();

        $data = array(
            "account_bank"=> $request->account_bank,
            "account_number"=> $request->account_number, 
            "amount"=> $request->amount,
            "currency"=> "NGN",
            "debit_currency"=> "NGN",
            "reference"=> $random = Str::random(30),
            "debit_subaccount"=> $user->wallet->account->account_reference
        );
        $response_transfer = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->post('https://api.flutterwave.com/v3/transfers', $data);
        $res_transfer = json_decode($response_transfer->getBody());
        // return $res_transfer;
        $transfer_id = Transfer::create([
            'user_id' => Auth::id(),
            'amount' => $res_transfer->data->amount,
            'ref' => $res_transfer->data->reference,
            'transfer_fee' => $res_transfer->data->fee,
            'transfer_id' => $res_transfer->data->id,
            'state' => $res_transfer->data->status,
            'status' => 'pending',
        ]);
        
        sleep(30);
        
        $response_retrieve = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->get('https://api.flutterwave.com/v3/transfers/'.$res_transfer->data->id);
        $res_retrieve = json_decode($response_retrieve->getBody());

        $transfer = Transfer::find($transfer_id->id);
        $transfer->update(['status'=> strtolower($res_retrieve->data->status)]);

        // $message_array = explode(":", $res_retrieve->data->complete_message);

        if(strtolower($res_retrieve->data->status) == "failed"){
            return ApiResponse::errorResponse($res_retrieve->data->complete_message);
        } else if (strtolower($res_retrieve->data->status) == "successful") {
            return ApiResponse::successResponse([
                'mesage'=>$res_retrieve->data->complete_message,
                'data' => $res_retrieve->data
            ]);
        } else {
            return ApiResponse::successResponse($res_retrieve->data->complete_message);
        }

        // return ApiResponse::successResponse(['data' => $res_retrieve->data]);
        // return ApiResponse::successResponse(['data' => $res_transfer->data]);
    }

    public function webhook (Request $request){

    }

    public function history ()
    {
        $user = User::where('id', Auth::id())->first();

        $response = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->get('https://api.flutterwave.com/v3/payout-subaccounts/'.$user->wallet->account->account_reference.'/transactions');
        $res = json_decode($response->getBody());
        // return $res;
        return ApiResponse::successResponse(['data' => $res->data]);
    }
}
