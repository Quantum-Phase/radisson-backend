<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\JobController;
use App\Models\StudentWork;
use App\Models\Work;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [ApiController::class, 'register'])->name('register');
Route::post('/login', [ApiController::class, 'login'])->name('login');

Route::group([
    "middleware" => ['auth:api']
], function () {
    Route::get('/profile', [ApiController::class, 'profile'])->name('profile');
    Route::get('/refresh', [ApiController::class, 'refreshToken'])->name('refresh');
    Route::get('/logout', [ApiController::class, 'logout'])->name('logout');

    //Route for Users
    Route::get('/user', [UserController::class, 'showUser'])->name('showUser');
    Route::post('/user', [UserController::class, 'insertUser'])->name('insertUser');
    Route::delete('/user/{userId}', [UserController::class, 'deleteUser'])->name('deleteUser');
    // Route::get('/{userId}', [UserController::class, 'update'])->name('update');
    Route::put('/user/{userId}', [UserController::class, 'updateUser'])->name('updateUser');

    //Route Course
    Route::get('/course', [CourseController::class, 'showCourse'])->name("ShowCourse");
    Route::post('/course', [CourseController::class, 'insertCourse'])->name("insertCourse");
    Route::delete('/course/{courseId}', [CourseController::class, 'deleteCourse'])->name("deleteCourse");
    // Route::get('/course/{courseId}', [CourseController::class, 'updatec'])->name('updatec');
    Route::put('/course/{courseId}', [CourseController::class, 'updateCourse'])->name('updateCourse');

    //Route Internship
    Route::get('/job', [JobController::class, 'showJob'])->name('ShowJob');
    Route::post('/job', [JobController::class, 'insertJob'])->name('InsertJob');
    Route::delete('/job/{workId}', [JobController::class, 'deleteJob'])->name('DeleteJob');
    Route::put('/job/{workId}', [JobController::class, 'updateJob'])->name('UpdateJob');

    //Route Fee
    Route::get('/fee', [FeeController::class, 'showFee'])->name("ShowFee");
    Route::post('/fee', [FeeController::class, 'insertFee'])->name('InsertFee');
    Route::delete('/fee/{feeId}', [FeeController::class, 'deleteFee'])->name('DeleteFee');
    Route::put('/fee/{feeId}', [FeeController::class, 'updateFee'])->name('UpdateFee');
});
