<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Http\Resources\Api\V1\OrderCollection;
use App\Models\Apartment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\TryCatch;
use Unicodeveloper\Paystack\Facades\Paystack;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::find(Auth::id());
        if($user->role == 'client'){
            $orders = Order::where('user_id', Auth::id())->latest()->paginate();
            return new OrderCollection($orders);
        } else if ($user->role == 'agent') {
            $orders = Order::where('agent_id', Auth::id())->with('apartment')->latest()->paginate();
            return new OrderCollection($orders);
        }
    }

    public function index_pending()
    {
        $user = User::find(Auth::id());
        if($user->role == 'client'){
            $orders = Order::where('user_id', Auth::id())->where('is_pending', true)->with('apartment')->latest()->paginate();
            return new OrderCollection($orders);
        } else if ($user->role == 'agent') {
            $orders = Order::where('agent_id', Auth::id())->where('is_pending')->with('apartment')->latest()->paginate();
            return new OrderCollection($orders);
        }
    }
    
    public function index_successful()
    {
        $user = User::find(Auth::id());
        if($user->role == 'client'){
            $orders = Order::where('user_id', Auth::id())->where('is_pending', false)->where('is_accepted', true)->with('apartment')->latest()->paginate();
            return new OrderCollection($orders);
        } else if ($user->role == 'agent') {
            $orders = Order::where('agent_id', Auth::id())->where('is_pending', false)->where('is_accepted', true)->with('apartment')->latest()->paginate();
            return new OrderCollection($orders);
        }
    }
    
    public function index_declined()
    {
        $user = User::find(Auth::id());
        if($user->role == 'client'){
            $orders = Order::where('user_id', Auth::id())->where('is_pending', false)->where('is_accepted', false)->latest()->paginate();
            return new OrderCollection($orders);
        } else if ($user->role == 'agent') {
            $orders = Order::where('agent_id', Auth::id())->where('is_pending', false)->where('is_accepted', false)->with('apartment')->latest()->paginate();
            return new OrderCollection($orders);
        }
        
    }

    public function indexAgent()
    {
        // $orders = Order::where('agent_id', Auth::id())->latest()->paginate();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'date' => 'required|date_equals:today|date_format:d-m-Y',
            'time' => 'required|date_format:h:i A',
            'apartment_id' => 'required|exists:apartments,id',
            'use_wallet' => 'required|boolean'
        ];

        $validation = Validator::make($request->all(), $rules);

        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
            ]);
        }

        $apartment = Apartment::where('id', $request->apartment_id)->first();
        $user = User::where('id', $apartment->user_id)->first();

        // $response = Http::withHeaders([
        //     "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
        //     "Cache-Control" => 'no-cache',
        // ])->get('https://api.flutterwave.com/v3/payout-subaccounts/'.$user->wallet->account->account_reference);
        // $res = json_decode($response->getBody());

        // return $res;

        $response = Http::withHeaders([
            "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
            "Cache-Control" => 'no-cache',
        ])->get('https://api.flutterwave.com/v3/payout-subaccounts/'.$user->wallet->account->account_reference.'/balances');
        $res = json_decode($response->getBody());

        if($res->data->available_balance < $apartment->price_yearly){
            return ApiResponse::errorResponse('Insufficient wallet balance', 400);
        }

        $order = Order::create([
            'user_id' => Auth::id(),
            'date' => $request->date,
            'apartment_id' => $request->apartment_id,
            'time' => $request->time,
            'use_wallet' => $request->use_wallet,
            'agent_id' => $apartment->user_id,
        ]);
        return response()->json([
            'order' => $order,
            'notification' => NotificationController::Notify($apartment->user_id, "Order has been placed on $apartment->apartment_type at $apartment->location, date by $user->name"),
        ], 201);   
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::where('id', $id)->with('apartment')->first();
        return response()->json([
            'data' => $order,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function action(Request $request, string $id)
    {
        $rules = [
            'is_accepted' => 'sometimes|boolean'
        ];

        $validation = Validator::make($request->all(), $rules);
        $validatedData = $request->all();
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
            ]);
        }

        $order = Order::where('id', $id)->first();
        $apartment = Apartment::where('id', $order->apartment_id)->first();
        $agent = User::where('id', $apartment->user_id)->first();
        $client = User::where('id', $order->user_id)->first();
        $order->update([
            // 'is_accepted' => $request->is_accepted,
            'is_pending' => false,
        ]);
        
        if($request->is_accepted == true){
            $random = Str::random(20);
            if($order->use_wallet == true){
                $data = array(
                    "account_bank"=> "flutterwave",
                    "account_number"=> $agent->wallet->account->barter_id,
                    "amount"=> $apartment->price_yearly,
                    "currency"=> "NGN",
                    "debit_currency"=> "NGN",
                    "reference"=> $random,
                    "debit_subaccount"=> $client->wallet->account->account_reference
                );

                $response = Http::withHeaders([
                    "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
                    "Cache-Control" => 'no-cache',
                ])->post('https://api.flutterwave.com/v3/transfers', $data);
                $res = json_decode($response->getBody());

                if($res->data->is_approved == 1){
                    $order->update([
                        'is_accepted' => true,
                        'pay_ref' => $random
                    ]);
                    return ApiResponse::successResponse('Offer has been accepted and your wallet credited');
                    NotificationController::Notify($apartment->user_id, "Offer has been accepted on $apartment->apartment_type at $apartment->location and $apartment->price_yearly naira has been credited to your wallet");
                    NotificationController::Notify($order->user_id, "Offer has been accepted on $apartment->apartment_type at $apartment->location and $apartment->price_yearly naira has been deducted to your wallet");

                } else {
                    $order->update([
                        'is_accepted' => false,
                        'pay_ref' => $random
                    ]);
                    NotificationController::Notify($apartment->user_id, "Offer on $apartment->apartment_type at $apartment->location has been declined due to payment error");
                    NotificationController::Notify($order->user_id, "Offer on $apartment->apartment_type at $apartment->location has been declined due to payment error. If you get charged, please contact support with your payment ref for refund");
                    return ApiResponse::errorResponse('Payment was not approved. The order has been rejected.');
                }
            } else {
                //Hopefully is_accepted will always be true
            }

        } else {
            $user = User::where('id', $order->agent_id)->first();
            $user->update(['offers_declined' => $user->offers_declined + 1]);
            if($user->offers_declined > 3){
                $data = array(
                    "account_bank"=> "044",
                    "account_number"=> "0690000031", 
                    "amount"=> 1000,
                    "currency"=> "NGN",
                    "debit_currency"=> "NGN",
                    "reference"=> $random = Str::random(20),
                    "debit_subaccount"=> $client->wallet->account->account_reference
                );

                $response = Http::withHeaders([
                    "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
                    "Cache-Control" => 'no-cache',
                ])->post('https://api.flutterwave.com/v3/transfers', $data);
                $res = json_decode($response->getBody());

                if($res->data->is_approved == 1){
                    $order->update([
                        'is_accepted' => true,
                    ]);
                    NotificationController::Notify($apartment->user_id, "Offer has been rejected on $apartment->apartment_type at $apartment->location and 1000 naira has been deducted from your wallet for exceeding three daily rejects");
                    NotificationController::Notify($order->user_id, "Offer has been rejected on $apartment->apartment_type at $apartment->location");
                    return ApiResponse::successResponse('Offer has been declined and 1000 naira deducted from your account.');   
                } else {
                    $user->wallet->update(['amount' => $user->wallet->amount + -1000]);
                    NotificationController::Notify($apartment->user_id, "Offer has been rejected on $apartment->apartment_type at $apartment->location and 1000 naira has been deducted from your wallet for exceeding three daily rejects");
                    NotificationController::Notify($order->user_id, "Offer has been rejected on $apartment->apartment_type at $apartment->location");
                    return ApiResponse::errorResponse('Payment was not approved. The 1000 naira will be deducted on your next credit.');
                }
            } else{
                NotificationController::Notify($apartment->user_id, "Offer has been rejected on $apartment->apartment_type at $apartment->location");
                NotificationController::Notify($order->user_id, "Offer has been rejected on $apartment->apartment_type at $apartment->location");
                return ApiResponse::successResponse('Offer has been declined');  
            }
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
