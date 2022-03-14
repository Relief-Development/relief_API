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
    Route::post('/recoverPassword',[UsersController::class,'recoverPassword']);
    Route::get('/search',[UsersController::class,'search']);
    Route::get('/getTherapistInMap',[UsersController::class, 'getTherapistInMap']);
});

Route::middleware('apitoken')->prefix('users')->group(function(){
    Route::post('/addFavorites',[UsersController::class, 'addFavorites']);
    Route::get('/getFavorites',[UsersController::class, 'getFavorites']);
    Route::post('/removeFavorites',[UsersController::class, 'removeFavorites']);  
    Route::post('/editProfile',[UsersController::class,'editProfile']);
    Route::post('/addRemoveFavorites',[UsersController::class, 'addRemoveFavorites']);      
});

Route::post('/login',[UsersController::class, 'login']);