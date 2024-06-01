<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $notifications = Auth::user()->notifications()
                                ->orderBy('created_at', 'desc')->get();

            return $this->successResponse('notifications fetched successfully', $notifications, 200);
        } catch (\Exception $e) {
            $this->errorResponse('failed to fetch notifications', $this->formatException($e), 500);
        }
    }

    public function markAsRead($id)
    {
        try {
            $notification = Notification::find($id);
            if(!$notification) return $this->errorResponse('notification not found', [], 404);

            $notification->is_read = 1;
            $notification->save();

            return $this->successResponse('notification marked as read successfully', $notification, 200);
        } catch (\Exception $e) {
            $this->errorResponse('failed to mark notification as read', $this->formatException($e), 500);
        }
    }

    public function markAllAsRead()
    {
        try {
            $notifications = Auth::user()->notifications;

            foreach ($notifications as $notification) {
                $notification->is_read = 1;
                $notification->save();
            }

            return $this->successResponse('all notifications marked as read successfully', null, 200);
        } catch (\Exception $e) {
            $this->errorResponse('failed to mark all notifications as read', $this->formatException($e), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
