<?php

namespace App\Traits;

use App\Events\NotificationEvent;
use App\Models\Notification as NotificationModel;

trait Notification {
    public function createAndDispatchNotification(string $type, array $data, int $userId){
      $notification = NotificationModel::create([
        'type' => $type,
        'data' => $data,
        'user_id' => $userId
      ]);

      NotificationEvent::dispatch($notification);

      return $notification;
    }
}