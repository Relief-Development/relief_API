<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MassagesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('users')->group(function(){
    Route::put('/registerUser',[UsersController::class,'registerUser']);
    Route::put('/registerMassage',[MassagesController::class,'registerMassage']);
});

Route::post('/login',[UsersController::class, 'login']);