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
    Route::post('/search',[UsersController::class,'search']);
    Route::get('/listMassages',[UsersController::class,'listMassages']);
    Route::post('/detailMassage',[UsersController::class,'detailMassage']);
    Route::post('/searchTherapistInMap',[UsersController::class, 'searchTherapistInMap']);
    Route::post('/getTherapistInMap',[UsersController::class, 'getTherapistInMap']);
    Route::post('/getTherapistForMassage',[UsersController::class,'getTherapistForMassage']);
    Route::post('/addRemoveService',[UsersController::class,'addRemoveService']);
});

Route::middleware('apitoken')->prefix('users')->group(function(){
    Route::post('/addRemoveFavorites',[UsersController::class, 'addRemoveFavorites']);  
    Route::post('/getFavorites',[UsersController::class, 'getFavorites']);
    Route::post('/editProfile',[UsersController::class,'editProfile']);    
    Route::post('/getServices',[UsersController::class,'getServices']);
    Route::post('/getRecommendedTherapists',[UsersController::class,'getRecommendedTherapists']);
});

Route::post('/login',[UsersController::class, 'login']);