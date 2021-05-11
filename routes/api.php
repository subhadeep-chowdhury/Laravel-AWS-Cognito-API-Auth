<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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

Route::post('/login', [AuthController::class, 'login']);

// Protected Routes - allows only logged in users
Route::middleware('auth.cognito')->group(function () {
	Route::get('/user', [AuthController::class, 'user']);

	Route::get('/logout', [AuthController::class, 'logout']);
});