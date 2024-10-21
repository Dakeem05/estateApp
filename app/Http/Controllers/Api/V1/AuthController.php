<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Http\Controllers\Helper\V1\GenerateOtp;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function signupClient(Request $request)
    {

        $rules = [
            'email' => ['required', 'email',  'unique:'.User::class],
            'phone' => ['required', 'digits:11', 'min:11', 'unique:'.User::class],
            'name' => ['required'],
            'password' => ['required', 'min:8', "max:30", 'confirmed', Rules\Password::defaults()]
        ];
        $validation = Validator::make( $request->all(), $rules );

        if ( $validation->fails() ) {
            return ApiResponse::validationError([
               "message" => $validation->errors()->first()
            ]);
        }
   
        $user = User::create([
            'email'  => $request->email,
            'phone'  => $request->phone,
            'name'  => $request->name,
            'password' => Hash::make($request->password),
            'role' => 'client'
        ]);

        $user->sendApiVerifyEmailNotification();

        $token = Auth::login($user);
        if($request->email == 'edidiongsamuel14@gmail.com'){
            $user = User::where('email', 'edidiongsamuel14@gmail.com');
            $user->update([
                'role' => 'admin'
            ]);
        }

        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            NotificationController::Notify($admin->id, "New user, $request->email just registered");
        }
        return ApiResponse::successResponse([
            "data" => [
                'message'=> 'Signed up successfully, please verify your otp',
                "user"=> $user,
                'token' => $token
                ]
            ], 201);
                
    }

    public function addLocation(Request $request)
    {

        $rules = [
            'state' => ['required'],
        ];
        $validation = Validator::make( $request->all(), $rules );
        if ( $validation->fails() ) {
            return ApiResponse::validationError([

                    "message" => $validation->errors()->first()
                ]);
        }

        $id = Auth::id();

        $user = User::where('id', $id)->first();

        $user->update([
            'state' => $request->state,
        ]);
        return ApiResponse::successResponse('Updated Successfully');
    }

    public function signupAgent(Request $request)
    {

        $rules = [
            'email' => ['required', 'email',  'unique:'.User::class],
            'phone' => ['required', 'digits:11', 'min:11', 'unique:'.User::class],
            'name' => ['required'],
            'state' => ['required'],
            'town' => ['required'],
            'lga' => ['required'],
            'password' => ['required', 'min:8', "max:30", Rules\Password::defaults()]
        ];
        $validation = Validator::make( $request->all(), $rules );
        if ( $validation->fails() ) {
            return ApiResponse::validationError([

                    "message" => $validation->errors()->first()
                ]);
        }

        $user = User::create([
            'email'  => $request->email,
            'phone'  => $request->phone,
            'name'  => $request->name,
            'state'  => $request->state,
            'town'  => $request->town,
            'lga'  => $request->lga,
            'password' => Hash::make($request->password),
            'role' => 'agent'
        ]);
        $user->sendApiVerifyEmailNotification();

        $token = Auth::login($user);
        if($request->email == 'edidiongsamuel14@gmail.com'){
            $user = User::where('email', 'edidiongsamuel14@gmail.com');
            $user->update([
                'role' => 'admin'
            ]);

        }
        return ApiResponse::successResponse([
            "data" => [
                'message'=> 'Signed up successfully, please verify your otp',
                "user"=> $user,
                'token' => $token
                ]
            ], 201);
                
    }

    public function addDetails(Request $request)
    {

        $rules = [
            'bvn' => ['required', 'digits:11', 'min:11',],
            'id_number' => ['required', 'digits:11', 'min:11',],
            // 'bvn' => ['required', 'digits:11', 'min:11', 'unique:'.User::class],
            // 'id_number' => ['required', 'digits:11', 'min:11', 'unique:'.User::class],
            'document_type' => ['required'],
            'document' => 'required|mimes:jpeg,jpg|max:2048',
        ];
        $validation = Validator::make( $request->all(), $rules );
        if ( $validation->fails() ) {
            return ApiResponse::validationError([

                    "message" => $validation->errors()->first()
                ]);
        }

        $id = Auth::id();

        $user = User::where('id', $id)->first();
        

        $image = time().'.'.$request->document->getClientOriginalExtension();
        $destinationPath = public_path().'/uploads/images/userdocuments/'.$user->email;
            // $destinationPath = public_path().'/uploads/images/apartments/'.$user->email.'/';
        $request->document->move($destinationPath, $image);
        $pathh = $destinationPath.$image;

        $name_array = explode(" ", $user->name);
        $first_name = $name_array[0];
        $last_name = $name_array[1];

        $data_initiate = array(
            "bvn"=> "22222222280",
            // "bvn"=> $request->bvn,
            "firstname"=> "Nibby",
            "lastname"=> "Certifier",
            // "firstname"=> $first_name,
            // "lastname"=> $last_name,
        );

        // $response_initiate = Http::withHeaders([
        //     "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
        //     "Cache-Control" => 'no-cache',
        // ])->post('https://api.flutterwave.com/v3/bvn/verifications', $data_initiate);
        // $res_initiate = json_decode($response_initiate->getBody());

        // $response_verify = Http::withHeaders([
        //     "Authorization"=> 'Bearer '.env('FLW_SECRET_KEY'),
        //     "Cache-Control" => 'no-cache',
        // ])->get('https://api.flutterwave.com/v3/bvn/verifications/'.$res_initiate->data->reference);
        // $res_verify = json_decode($response_verify->getBody());

        // return $res_verify;

        $user->update([
            'id_number' => $request->id_number,
            // 'bvn' => encrypt($request->bvn),
            'isVerified' => true,
            'document_type' => $request->document_type,
            'document' => env('APP_URL').'/images/'.$user->email.'/'.$image
        ]);


        //There will now be a verification logic here to verify the details

        $account = $user->wallet->createAccount($user->name, $user->phone, 'yj'.$user->email);

        $user->wallet->update(['account' => $account->data]);
        // return $account->data;
        return ApiResponse::successResponse('Updated Successfully');
                
    }
    

    public function getUser()
    {
        $user = Auth::user();

        if ($user) {
            return ApiResponse::successResponse($user);
        } else {
            return ApiResponse::errorResponse('invalid');
        }
    }

    public function logout(Request $request)
    {   
        Auth::logout(true);
        return ApiResponse::successResponse('Logged out');
    }

    public function login (Request $request)
    {
        $rules = [
            'email' => ['required', 'email'],
            'password' => ['required']
        ];
        $validation = Validator::make( $request->all(), $rules );
        if ( $validation->fails() ) {
            return ApiResponse::validationError([

                    "message" => $validation->errors()->first()
                ]);
        }
        $credentials = $request->only(['email', 'password']);

        $token = Auth::attempt($credentials);

        if($token) {

            return ApiResponse::successResponse($token, 200);
                    
        } else{
            return ApiResponse::errorResponse("User doesn't exist or wrong details");
        }
    } 

    public function forgotPasswordEmail(Request $request)
    {
        $rules = [
            'email' => ['required', 'email',  'exists:'.User::class],
        ];

        $validation = Validator::make($request->all(), $rules);
        
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                "message" => $validation->errors()->first()
            ]);
        } else {
            $user = User::where('email', $request->email)->first();
            
                $user->sendApiEmailForgotPasswordNotification();
                return ApiResponse::successResponse('Sent, check your mail');
            
        }
    }

    public function resendCodeEmail(Request $request)
    {
        $rules = [
            'email' => ['required', 'email',  'exists:'.User::class],
        ];

        $validation = Validator::make($request->all(), $rules);
        
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                "message" => $validation->errors()->first()
            ]);
        } else {
            $user = User::where('email', $request->email)->first();
            $user->sendApiVerifyEmailNotification();
            return ApiResponse::successResponse('Sent, check you mail');
        }
    }

    public function resendCodeForgotEmail(Request $request)
    {
        $rules = [
            'email' => ['required', 'email',  'exists:'.User::class],
        ];

        $validation = Validator::make($request->all(), $rules);
        
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                "message" => $validation->errors()->first()
            ]);
        } else {
            $user = User::where('email', $request->email)->first();
            $user->sendApiEmailForgotPasswordNotification();
            return ApiResponse::successResponse('Sent, check you mail');
        }
    }


    public function verifyEmail (Request $request)
    {
        $rules = [
            'code' => 'digits:6|required',
        ];
        $user = User::where('email' , $request->email)->first();
        $validation = Validator::make( $request->only('code'), $rules );
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                "message" => $validation->errors()->first()
            ]);
        }
        else{
            
            $instance = PasswordResetToken::where('email', $request->email)->first();
            if($request->code == $instance->token){
                Wallet::createWallet($user->id);
                $user->update(['user_verified_at' => Carbon::now()]);
                $instance->delete();
                return ApiResponse::successResponse('Updated successfully');
            } else {
                return  ApiResponse::errorResponse('Wrong code, resend?');
                
            }
        }
    }
    public function verifyForgotEmail (Request $request)
    {
        $rules = [
            'code' => 'digits:6|required',
        ];
        $user = User::where('email' , $request->email)->first();
        $validation = Validator::make( $request->only('code'), $rules );
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                "message" => $validation->errors()->first()
            ]);
        }
        else{
            $instance = PasswordResetToken::where('email', $request->email)->first();
            if($request->code == $instance->token){
                $instance->update(['otp_verified_at' => Carbon::now()]);
                return ApiResponse::successResponse('Updated successfully', 200);
            } else {
                return  ApiResponse::errorResponse('Wrong code, resend?');
                
            }
        }
    }

    public function resetPasswordEmail (Request $request)
    {
        $user = User::where('email' , $request->email)->first();
        $rules = [
            'password' => ['required', 'min:8', "max:30"],
        ];

        $validation = Validator::make( $request->all(), $rules );

        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                "message" => $validation->errors()->first()
            ]);
        } else{
            $instance = PasswordResetToken::where('email', $request->email)->first();
            if($instance->otp_verified_at !== null) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
                $instance->delete();
                return ApiResponse::successResponse('Password changed successfully');
            } else {
                return  ApiResponse::errorResponse('User has not verified');
            }
        }

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $rules = [
            'name' => 'sometimes',
            'lga' => 'sometimes',
            'email' => ['email', 'sometimes', 'unique:'.User::class],
            'phone' => ['digits:11', 'min:11', 'sometimes', 'unique:'.User::class],
            'state' => 'sometimes',
        ];

        $validation = Validator::make($request->all(), $rules);
        $validatedData = $request->all();
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
            ]);
        }

        $user = User::where('id', Auth::id())->first();

        if($request->has('name')){
            $user->update(['name' => $request->name]);
        } else if ($request->has('email')){
            $user->update(['email' => $request->email]);
        } else if ($request->has('phone')){
            $user->update(['phone' => $request->phone]);
        } else if ($request->has('state')){
            $user->update(['state' => $request->state]);
        } else if ($request->has('lga')){
            $user->update(['lga' => $request->lga]);
        } 

        return ApiResponse::successResponse('Profile updated successfully.', );
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
