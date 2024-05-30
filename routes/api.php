<?php

use App\Events\Test;
use App\Models\Post;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostLikeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserFollowController;
use Illuminate\Support\Facades\Route;

// Public
Route::group(["prefix" => "auth"], function () {
    Route::post("/sign-up", [AuthController::class, 'signUp']);
    Route::post("/sign-in", [AuthController::class, 'signIn']);
    Route::post("/forgot-password", [AuthController::class, 'forgotPassword']);
    Route::post("/reset-password", [AuthController::class, 'resetPassword']);
    Route::post("/verify-email", [AuthController::class, 'verifyEmail']);
    Route::post("/verify-email/resend", [AuthController::class, 'verifyEmailResend']);
});

Route::post("/test-broadcast", function (){
    $post = Post::first();
    Test::dispatch($post);

    return response()->json([
        "success" => true
    ]);
});

// Protected
Route::group([
    "middleware" => ["auth:sanctum", "verified"],
], function () {
    // Auth
    Route::group(["prefix" => "auth"], function () {
        Route::get("/sign-out", [AuthController::class, 'signOut']);
    });

    // User
    Route::group(["prefix" => "users"], function () {
        Route::get("/", [UserController::class, 'index']);
        Route::get("/{id}", [UserController::class, 'show']);
        Route::get("/me", [UserController::class, 'me']);
        Route::get("/posts", [UserController::class, 'posts']);
        Route::get("/posts/liked", [UserController::class, 'likedPosts']);
        Route::post("/change-password", [UserController::class, 'changePassword']);
        Route::patch("/update-profile", [UserController::class, 'updateProfile']);

        Route::post("/follow/send/{receiver_id}", [UserFollowController::class, 'sendFollowRequest']);
        Route::post("/follow/accept/{sender_id}", [UserFollowController::class, 'acceptFollowRequest']);
        Route::post("/follow/reject/{sender_id}", [UserFollowController::class, 'rejectFollowRequest']);
        Route::post("/follow/remove/{sender_id}", [UserFollowController::class, 'removeFollowRequest']);
        Route::post("/follow/cancel-or-unfollow/{receiver_id}", [UserFollowController::class, 'cancelOrUnFollowFollowRequest']);
    });

    // Post
    Route::group(["prefix" => "posts"], function () {
        Route::get("/", [PostController::class, 'index']);
        Route::get("/{id}", [PostController::class, 'show']);
        Route::post("/", [PostController::class, 'store']);
        Route::post("/{id}", [PostController::class, 'update']);
        Route::delete("/{id}", [PostController::class, 'destroy']);

        // Like
        Route::post("/like-unlike/{post_id}", [PostLikeController::class, 'likeUnlike']);

        // Comment
        Route::group(["prefix" => "comments"], function () {
            Route::post("/{post_id}", [PostCommentController::class, 'store']);
            Route::get("/{id}", [PostCommentController::class, 'show']);
            Route::patch("/{id}", [PostCommentController::class, 'update']);
            Route::delete("/{id}", [PostCommentController::class, 'destroy']);
        });
    });
});