<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(["prefix" => "auth"], function () {
    Route::post("/sign-up", [AuthController::class, 'signUp']);
    Route::post("/sign-in", [AuthController::class, 'signIn']);
    Route::post("/forgot-password", [AuthController::class, 'forgotPassword']);
    Route::post("/reset-password", [AuthController::class, 'resetPassword']);
    Route::post("/verify-email", [AuthController::class, 'verifyEmail']);
    Route::post("/verify-email/resend", [AuthController::class, 'verifyEmailResend']);
});

Route::group([
    "middleware" => ["auth:sanctum", "verified"],
], function () {
    Route::group(["prefix" => "auth"], function () {
        Route::get("/sign-out", [AuthController::class, 'signOut']);
    });

    Route::group(["prefix" => "users"], function () {
        Route::get("/me", [UserController::class, 'me']);
    });
});