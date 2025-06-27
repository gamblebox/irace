<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Open Routes
Route::post("register", [ApiController::class, "register"]);
Route::post("login", [ApiController::class, "login"]);

// Protected Routes
Route::group([
    "middleware" => ["auth:api"]
], function () {
    Route::get("profile", [ApiController::class, "profile"]);
    Route::get("logout", [ApiController::class, "logout"]);
});

// race Routes
Route::post("createBranch", [ApiController::class, "createBranch"]);
Route::post("getTodayRace", [ApiController::class, "getTodayRace"]);
Route::post("getAssociationInfo", [ApiController::class, "getAssociationInfo"]);
Route::post("getRaceChangeInfo", [ApiController::class, "getRaceChangeInfo"]);
Route::post("getRaceResult", [ApiController::class, "getRaceResult"]);
Route::post("getRaceInfo", [ApiController::class, "getRaceInfo"]);
