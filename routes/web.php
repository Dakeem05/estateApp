<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/images/{email}/{image}', function ($email, $image) {
    $basePath = public_path() . '/uploads/images/';

    if (File::exists($basePath . 'userdocuments/'.$email .'/' . $image)) {
        return response()->file($basePath . 'userdocuments/'.$email .'/'. $image);
    } elseif (File::exists($basePath . 'apartments/'.$email .'/' . $image)) {
        return response()->file($basePath . 'apartments/'.$email .'/' . $image);
    } else {
        return response('Not found', 404);
    }
});
