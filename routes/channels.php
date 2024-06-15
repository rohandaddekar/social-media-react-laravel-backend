<?php

use App\Http\Resources\ChatUserResource;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user-follow-status.{id}', function () { return true; });

Broadcast::channel('chat-online-status', function ($user) { 
    return $user ? new ChatUserResource($user) : null; 
});
Broadcast::channel('chat-message.{id}', function () { return true; });

Broadcast::channel('notification.{user_id}', function () { return true; });

Broadcast::channel('post', function () { return true; });
Broadcast::channel('post-comment', function () { return true; });
Broadcast::channel('post-like', function () { return true; });
