<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user-follow-status.{id}', function ($user, $id) {
    return true;
});

Broadcast::channel('notification.{user_id}', function ($user, $user_id) {
    return true;
});
