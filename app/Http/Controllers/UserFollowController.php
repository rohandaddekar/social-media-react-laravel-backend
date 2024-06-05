<?php

namespace App\Http\Controllers;

use App\Events\UserFollowStatusEvent;
use App\Models\User;
use App\Models\UserFollow;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserFollowController extends Controller
{
    use ApiResponse, Notification;

    public function sendFollowRequest(Request $request, $receiver_id){
        try {
            $receiver_user = User::find($receiver_id);
            if(!$receiver_user) return $this->errorResponse('user not found', null, 404);

            $sender_user = Auth::user();

            $alreadyReqSent = $sender_user->sentFollowRequests()->where('receiver_id', $receiver_id)->first();
            if($alreadyReqSent) return $this->errorResponse('request already sent', null, 400);

            $followReq = $sender_user->sentFollowRequests()->create([
                'receiver_id' => $receiver_id,
                'status' => 'pending'
            ]);

            UserFollowStatusEvent::dispatch($followReq, [
                'sender_follow_status' => "pending_sent",
                'receiver_follow_status' => "pending_received"
            ]);

            $this->createAndDispatchNotification('user-follow', [
                'message' => $sender_user->first_name . " " . $sender_user->last_name . ' sent you a follow request',
                'user' => $sender_user
            ], $receiver_id);

            return $this->successResponse('follow request sent successfully', $followReq, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to send follow request', $this->formatException($e), 500); 
        }
    }
    
    public function acceptFollowRequest(Request $request, $sender_id){
        try {
            $sender_user = User::find($sender_id);
            if(!$sender_user) return $this->errorResponse('user not found', null, 404);

            $receiver_user = Auth::user();

            $followReq = UserFollow::where('sender_id', $sender_id)
                                    ->where('receiver_id', $receiver_user->id)
                                    ->first();
            if(!$followReq) return $this->errorResponse('request not found', null, 404);

            $followReq->status = 'accepted';
            $followReq->save();

            UserFollowStatusEvent::dispatch($followReq, [
                'sender_follow_status' => "follower",
                'receiver_follow_status' => "following"
            ]);

            $this->createAndDispatchNotification('user-follow', [
                'message' => $receiver_user->first_name . " " . $receiver_user->last_name . ' accepted your follow request',
                'user' => $receiver_user
            ], $sender_id);

            return $this->successResponse('follow request accepted successfully', $followReq, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to accept follow request', $this->formatException($e), 500); 
        }
    }
    
    public function rejectFollowRequest(Request $request, $sender_id){
        try {
            $sender_user = User::find($sender_id);
            if(!$sender_user) return $this->errorResponse('user not found', null, 404);

            $receiver_user = Auth::user();

            $followReq = UserFollow::where('sender_id', $sender_id)
                                    ->where('receiver_id', $receiver_user->id)
                                    ->first();
            if(!$followReq) return $this->errorResponse('request not found', null, 404);

            UserFollowStatusEvent::dispatch($followReq, [
                'sender_follow_status' => "none",
                'receiver_follow_status' => "none"
            ]);

            $this->createAndDispatchNotification('user-follow', [
                'message' => $receiver_user->first_name . " " . $receiver_user->last_name . ' rejected your follow request',
                'user' => $receiver_user
            ], $sender_id);
            
            $followReq->delete();

            return $this->successResponse('follow request rejected successfully', $followReq, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to reject follow request', $this->formatException($e), 500); 
        }
    }
    
    public function removeFollowRequest(Request $request, $sender_id){
        try {
            $sender_user = User::find($sender_id);
            if(!$sender_user) return $this->errorResponse('user not found', null, 404);

            $receiver_user = Auth::user();

            $followReq = UserFollow::where('sender_id', $sender_id)
                                    ->where('receiver_id', $receiver_user->id)
                                    ->first();
            if(!$followReq) return $this->errorResponse('request not found', null, 404);

            UserFollowStatusEvent::dispatch($followReq, [
                'sender_follow_status' => "none",
                'receiver_follow_status' => "none"
            ]);

            $followReq->delete();

            return $this->successResponse('follow request removed successfully', $followReq, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to remove follow request', $this->formatException($e), 500); 
        }
    }
    
    public function cancelOrUnFollowFollowRequest(Request $request, $receiver_id){
        try {
            $receiver_user = User::find($receiver_id);
            if(!$receiver_user) return $this->errorResponse('user not found', null, 404);

            $sender_user = Auth::user();

            $followReq = UserFollow::where('sender_id', $sender_user->id)
                                    ->where('receiver_id', $receiver_id)
                                    ->first();
            if(!$followReq) return $this->errorResponse('request not found', null, 404);

            UserFollowStatusEvent::dispatch($followReq, [
                'sender_follow_status' => "none",
                'receiver_follow_status' => "none"
            ]);

            $followReq->delete();

            return $this->successResponse('unfollowed or cancelled follow request successfully', $followReq, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to unfollow or cancel follow request', $this->formatException($e), 500); 
        }
    }
    
    public function followers($user_id){
        try {
            $user = User::find($user_id);
            if(!$user) return $this->errorResponse('user not found', null, 404);

            $followers = $user->receivedFollowRequests->where('status', 'accepted');

            return $this->successResponse('successfully fetched followers', $followers, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch followers', $this->formatException($e), 500); 
        }
    }
    
    public function followings($user_id){
        try {
            $user = User::find($user_id);
            if(!$user) return $this->errorResponse('user not found', null, 404);

            $followings = $user->sentFollowRequests->where('status', 'accepted');

            return $this->successResponse('successfully fetched followings', $followings, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('failed to fetch followings', $this->formatException($e), 500); 
        }
    }
}
