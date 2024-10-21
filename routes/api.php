<?php

use App\Http\Controllers\Api\V1\ApartmentController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['middleware' => 'api', 'prefix' => '/V1'], function($router){

    Route::group(['prefix' => '/auth'], function($router){
        Route::post('signupClient', [AuthController::class,'signupClient']);
        Route::post('signupAgent', [AuthController::class,'signupAgent']);
        Route::post('/resendCodeEmail', [AuthController::class, 'resendCodeEmail']);
        Route::post('/verifyEmail', [AuthController::class, 'verifyEmail']);
        Route::post('/verifyForgotEmail', [AuthController::class, 'verifyForgotEmail']);
        Route::post('/forgotPasswordEmail', [AuthController::class, 'forgotPasswordEmail']);
        Route::post('/resendCodeForgotEmail', [AuthController::class, 'resendCodeForgotEmail']);
        Route::post('/resetPasswordEmail', [AuthController::class, 'resetPasswordEmail']);
        Route::post('/login', [AuthController::class, 'login']);
        
        //The commented endpoints below are for the phone number otp using twilio, I haven' built the functions yet so they're commented
        
        // Route::post('/forgotPasswordPhone', [AuthController::class, 'forgotPasswordPhone']);
        // Route::post('/verifyForgotPhone', [AuthController::class, 'verifyForgotPhone']);
        // Route::post('/resetPasswordPhone', [AuthController::class, 'resetPasswordPhone']);
        // Route::post('/resendCodePhone', [AuthController::class, 'resendCodePhone']);
        
        //The commented endpoints above are for the phone number otp using twilio, I haven't built the functions yet so they're commented
    });
    
    Route::group(['middleware' => 'auth:api'], function($router){
        Route::get('logout', [AuthController::class, 'logout']);

        Route::group(['middleware' => 'isAuthenticated'], function($router){
            Route::put('/updateProfile', [AuthController::class, 'update']);
            Route::get('getUser', [AuthController::class,'getUser']);
            Route::post('addLocation', [AuthController::class,'addLocation']);
            Route::post('addDetails', [AuthController::class,'addDetails']);
            
            Route::resource('notification', NotificationController::class);

            Route::get('wallet/options/view', [WalletController::class, 'index']);
            Route::get('wallet/options/banks', [WalletController::class, 'banks']);
            Route::post('wallet/options/resolve', [WalletController::class, 'resolve_account']);
            Route::post('wallet/options/transfer', [WalletController::class, 'transfer']);
            Route::get('wallet/options/history', [WalletController::class, 'history']);
            
            Route::group(['middleware' => 'isVerified'], function($router){
                Route::get('order/options/view', [OrderController::class, 'index']);
                Route::get('order/options/viewPending', [OrderController::class, 'index_pending']);
                Route::get('order/options/viewSuccessful', [OrderController::class, 'index_successful']);
                Route::get('order/options/viewDeclined', [OrderController::class, 'index_declined']);

                Route::resource('wallet', WalletController::class);

                Route::group(['middleware' => 'isClient', 'prefix' => '/client'], function($router){
                    Route::resource('apartment', ApartmentController::class);
                    Route::get('/apartment/options/view/{id}', [ApartmentController::class, 'view']);
                    Route::get('/apartment/options/nearby', [ApartmentController::class, 'nearby']);
                    Route::resource('appointment', AppointmentController::class);
                    Route::resource('order', OrderController::class);
                });

                Route::group(['middleware' => 'isAgent', 'prefix' => '/agent'], function($router){
                    Route::resource('apartment', ApartmentController::class);
                    Route::get('/apartment/options/list', [ApartmentController::class, 'listApartments']);
                    Route::put('/updateApartment', [ApartmentController::class, 'update']);
                    Route::post('appointment/action/{id}', [AppointmentController::class, 'action']);
                    Route::get('appointment/options/view', [AppointmentController::class, 'indexAgent']);
                    Route::get('order/options/{id}', [OrderController::class, 'show']);
                    Route::get('orders', [OrderController::class, 'indexAgent']);
                    Route::put('order/action/{id}', [OrderController::class, 'action']);
                });

            });

            Route::group(['middleware' => 'isAdmin', 'prefix' => '/admin'], function($router){
                //Admin endpoints will enter here
            });
        });
    });
});
